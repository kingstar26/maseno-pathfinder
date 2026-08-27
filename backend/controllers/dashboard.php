<?php

declare(strict_types=1);

function get_graduate_dashboard(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];

    // Resolve Graduate Profile ID
    $profileStmt = $connection->prepare(
        'SELECT gp.graduate_profile_id, u.first_name, u.last_name, u.email, u.avatar_file_id
         FROM users AS u
         INNER JOIN graduate_profiles AS gp ON gp.user_id = u.user_id
         WHERE u.user_id = ? LIMIT 1'
    );
    $profileStmt->bind_param('i', $userId);
    $profileStmt->execute();
    $userRow = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();

    if (!is_array($userRow)) {
        json_error('Graduate profile was not found.', [], 404);
    }

    $graduateProfileId = (int) $userRow['graduate_profile_id'];

    // 1. Profile Summary
    $prefStmt = $connection->prepare(
        'SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ? AND preference_key = \'avatar_url\' LIMIT 1'
    );
    $prefStmt->bind_param('i', $userId);
    $prefStmt->execute();
    $prefRow = $prefStmt->get_result()->fetch_assoc();
    $prefStmt->close();

    $avatarUrl = is_array($prefRow) && $prefRow['preference_value'] !== null ? trim((string) $prefRow['preference_value']) : null;

    if ($avatarUrl === null && $userRow['avatar_file_id'] !== null) {
        $avatarFileId = (int) $userRow['avatar_file_id'];
        $fileStmt = $connection->prepare('SELECT storage_key FROM files WHERE file_id = ? LIMIT 1');
        $fileStmt->bind_param('i', $avatarFileId);
        $fileStmt->execute();
        $fileRow = $fileStmt->get_result()->fetch_assoc();
        $fileStmt->close();
        if (is_array($fileRow) && !empty($fileRow['storage_key'])) {
            $avatarUrl = '/uploads/' . $fileRow['storage_key'];
        }
    }

    $completionPercentage = calculate_profile_completion($connection, $userId);

    $profileSummary = [
        'first_name' => (string) $userRow['first_name'],
        'last_name' => (string) $userRow['last_name'],
        'email' => (string) $userRow['email'],
        'avatar_url' => $avatarUrl,
        'profile_completion' => $completionPercentage,
        'profile_completion_percentage' => $completionPercentage,
    ];

    // 2. Employment Summary
    $empStmt = $connection->prepare(
        'SELECT 
            er.company_name,
            er.job_title,
            er.industry,
            er.employment_type,
            er.current_job,
            es.name AS employment_status
         FROM employment_records AS er
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         WHERE er.graduate_profile_id = ?
         ORDER BY er.current_job DESC, er.start_date DESC, er.employment_record_id DESC
         LIMIT 1'
    );
    $empStmt->bind_param('i', $graduateProfileId);
    $empStmt->execute();
    $empRow = $empStmt->get_result()->fetch_assoc();
    $empStmt->close();

    if (is_array($empRow)) {
        $employmentSummary = [
            'employment_status' => $empRow['employment_status'] !== null ? (string) $empRow['employment_status'] : null,
            'company_name' => $empRow['company_name'] !== null ? (string) $empRow['company_name'] : null,
            'job_title' => $empRow['job_title'] !== null ? (string) $empRow['job_title'] : null,
            'industry' => $empRow['industry'] !== null ? (string) $empRow['industry'] : null,
            'employment_type' => $empRow['employment_type'] !== null ? (string) $empRow['employment_type'] : null,
            'current_job' => $empRow['current_job'] !== null ? (int) $empRow['current_job'] : 0,
        ];
    } else {
        $employmentSummary = [
            'employment_status' => null,
            'company_name' => null,
            'job_title' => null,
            'industry' => null,
            'employment_type' => null,
            'current_job' => null,
        ];
    }

    // 3. Recommendation Summary
    $recCountStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM recommendation_requests WHERE graduate_profile_id = ?'
    );
    $recCountStmt->bind_param('i', $graduateProfileId);
    $recCountStmt->execute();
    $recCountRes = $recCountStmt->get_result()->fetch_assoc();
    $recCountStmt->close();
    $totalRecRequests = is_array($recCountRes) ? (int) $recCountRes['total'] : 0;

    $recPendingStmt = $connection->prepare(
        'SELECT COUNT(*) AS pending FROM recommendation_requests WHERE graduate_profile_id = ? AND request_status = \'Pending\''
    );
    $recPendingStmt->bind_param('i', $graduateProfileId);
    $recPendingStmt->execute();
    $recPendingRes = $recPendingStmt->get_result()->fetch_assoc();
    $recPendingStmt->close();
    $pendingRecRequests = is_array($recPendingRes) ? (int) $recPendingRes['pending'] : 0;

    $recApprovedStmt = $connection->prepare(
        'SELECT COUNT(*) AS approved FROM recommendation_requests WHERE graduate_profile_id = ? AND request_status = \'Approved\''
    );
    $recApprovedStmt->bind_param('i', $graduateProfileId);
    $recApprovedStmt->execute();
    $recApprovedRes = $recApprovedStmt->get_result()->fetch_assoc();
    $recApprovedStmt->close();
    $approvedRecRequests = is_array($recApprovedRes) ? (int) $recApprovedRes['approved'] : 0;

    $latestRecStmt = $connection->prepare(
        'SELECT request_status FROM recommendation_requests WHERE graduate_profile_id = ? ORDER BY requested_at DESC, recommendation_request_id DESC LIMIT 1'
    );
    $latestRecStmt->bind_param('i', $graduateProfileId);
    $latestRecStmt->execute();
    $latestRecRow = $latestRecStmt->get_result()->fetch_assoc();
    $latestRecStmt->close();
    $latestRecStatus = is_array($latestRecRow) ? (string) $latestRecRow['request_status'] : null;

    $recommendationSummary = [
        'total_recommendation_requests' => $totalRecRequests,
        'total_requests' => $totalRecRequests,
        'pending_requests' => $pendingRecRequests,
        'pending_requests_count' => $pendingRecRequests,
        'approved_requests' => $approvedRecRequests,
        'approved_requests_count' => $approvedRecRequests,
        'latest_request_status' => $latestRecStatus,
    ];

    // 4. Survey Summary
    $assignedSurveysStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM survey_invitations WHERE graduate_profile_id = ?'
    );
    $assignedSurveysStmt->bind_param('i', $graduateProfileId);
    $assignedSurveysStmt->execute();
    $assignedSurveysRes = $assignedSurveysStmt->get_result()->fetch_assoc();
    $assignedSurveysStmt->close();
    $totalAssignedSurveys = is_array($assignedSurveysRes) ? (int) $assignedSurveysRes['total'] : 0;

    if ($totalAssignedSurveys === 0) {
        $activeSurveysStmt = $connection->prepare(
            'SELECT COUNT(*) AS total FROM surveys WHERE is_active = 1 AND (available_from IS NULL OR available_from <= NOW(6)) AND (available_until IS NULL OR available_until >= NOW(6))'
        );
        $activeSurveysStmt->execute();
        $activeSurveysRes = $activeSurveysStmt->get_result()->fetch_assoc();
        $activeSurveysStmt->close();
        $totalAssignedSurveys = is_array($activeSurveysRes) ? (int) $activeSurveysRes['total'] : 0;
    }

    $completedSurveysStmt = $connection->prepare(
        'SELECT COUNT(DISTINCT si.survey_id) AS total
         FROM survey_invitations AS si
         LEFT JOIN survey_response_statuses AS srs ON srs.survey_response_status_id = si.survey_response_status_id
         WHERE si.graduate_profile_id = ?
           AND (si.completed_at IS NOT NULL OR srs.is_final = 1 OR UPPER(COALESCE(srs.code, \'\')) = \'SUBMITTED\')'
    );
    $completedSurveysStmt->bind_param('i', $graduateProfileId);
    $completedSurveysStmt->execute();
    $completedSurveysRes = $completedSurveysStmt->get_result()->fetch_assoc();
    $completedSurveysStmt->close();
    $completedSurveysCount = is_array($completedSurveysRes) ? (int) $completedSurveysRes['total'] : 0;

    $pendingSurveysCount = max(0, $totalAssignedSurveys - $completedSurveysCount);

    $surveySummary = [
        'total_assigned' => $totalAssignedSurveys,
        'total_assigned_surveys' => $totalAssignedSurveys,
        'completed_surveys' => $completedSurveysCount,
        'pending_surveys' => $pendingSurveysCount,
    ];

    // 5. Event Summary (Upcoming registered events belonging to logged-in graduate)
    $eventsStmt = $connection->prepare(
        'SELECT 
            e.event_id,
            e.title,
            e.venue,
            e.start_datetime,
            er.registration_status
         FROM event_registrations AS er
         INNER JOIN events AS e ON e.event_id = er.event_id
         WHERE er.graduate_profile_id = ?
           AND e.is_active = 1
           AND e.start_datetime >= NOW(6)
         ORDER BY e.start_datetime ASC, e.event_id ASC'
    );
    $eventsStmt->bind_param('i', $graduateProfileId);
    $eventsStmt->execute();
    $rawEvents = $eventsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $eventsStmt->close();

    $eventsSummary = [];
    foreach ($rawEvents as $eRow) {
        $eventsSummary[] = [
            'event_id' => (int) $eRow['event_id'],
            'title' => (string) $eRow['title'],
            'venue' => $eRow['venue'] !== null ? (string) $eRow['venue'] : null,
            'start_datetime' => (string) $eRow['start_datetime'],
            'registration_status' => (string) $eRow['registration_status'],
        ];
    }

    json_success(
        'Dashboard data retrieved successfully.',
        [
            'profile' => $profileSummary,
            'employment' => $employmentSummary,
            'recommendations' => $recommendationSummary,
            'surveys' => $surveySummary,
            'events' => $eventsSummary,
        ]
    );
}
