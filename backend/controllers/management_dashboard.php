<?php

declare(strict_types=1);

/**
 * Resolves the management_profile_id for an authenticated user.
 * If missing for a user with management role, auto-creates the profile.
 */
function management_profile_id(mysqli $connection, int $userId): int
{
    $statement = $connection->prepare('SELECT management_profile_id FROM management_profiles WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $profile = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (is_array($profile) && isset($profile['management_profile_id'])) {
        return (int) $profile['management_profile_id'];
    }

    $instStmt = $connection->prepare('INSERT INTO management_profiles (user_id, designation) VALUES (?, \'University Management\')');
    $instStmt->bind_param('i', $userId);
    $instStmt->execute();
    $mgmtProfileId = $connection->insert_id;
    $instStmt->close();

    return (int) $mgmtProfileId;
}

function get_management_dashboard(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    $managementProfileId = management_profile_id($connection, $userId);

    // 1. Total Graduates
    $gradStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM users AS u
         INNER JOIN roles AS r ON r.role_id = u.role_id
         WHERE r.code = \'graduate\''
    );
    $gradStmt->execute();
    $gradRes = $gradStmt->get_result()->fetch_assoc();
    $gradStmt->close();
    $totalGraduates = is_array($gradRes) ? (int) $gradRes['total'] : 0;

    if ($totalGraduates === 0) {
        $gpStmt = $connection->prepare('SELECT COUNT(*) AS total FROM graduate_profiles');
        $gpStmt->execute();
        $gpRes = $gpStmt->get_result()->fetch_assoc();
        $gpStmt->close();
        $totalGraduates = is_array($gpRes) ? (int) $gpRes['total'] : 0;
    }

    // 2. Employment Statistics
    $empRecordsStmt = $connection->prepare('SELECT COUNT(*) AS total FROM employment_records');
    $empRecordsStmt->execute();
    $empRecordsRes = $empRecordsStmt->get_result()->fetch_assoc();
    $empRecordsStmt->close();
    $totalEmpRecords = is_array($empRecordsRes) ? (int) $empRecordsRes['total'] : 0;

    $employedGradsStmt = $connection->prepare(
        'SELECT COUNT(DISTINCT graduate_profile_id) AS total FROM employment_records WHERE current_job = 1'
    );
    $employedGradsStmt->execute();
    $employedGradsRes = $employedGradsStmt->get_result()->fetch_assoc();
    $employedGradsStmt->close();
    $employedGraduates = is_array($employedGradsRes) ? (int) $employedGradsRes['total'] : 0;

    $byStatusStmt = $connection->prepare(
        'SELECT es.name AS status_name, COUNT(er.employment_record_id) AS count
         FROM employment_statuses AS es
         LEFT JOIN employment_records AS er ON er.employment_status_id = es.employment_status_id
         GROUP BY es.employment_status_id, es.name
         ORDER BY count DESC'
    );
    $byStatusStmt->execute();
    $rawByStatus = $byStatusStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $byStatusStmt->close();

    $byStatusList = [];
    foreach ($rawByStatus as $stRow) {
        $byStatusList[] = [
            'name' => (string) $stRow['status_name'],
            'count' => (int) $stRow['count'],
        ];
    }

    $employmentStats = [
        'total_records' => $totalEmpRecords,
        'employed_graduates' => $employedGraduates,
        'by_status' => $byStatusList,
    ];

    // 3. Tracer Survey Statistics
    $surveysCountStmt = $connection->prepare('SELECT COUNT(*) AS total FROM surveys');
    $surveysCountStmt->execute();
    $surveysCountRes = $surveysCountStmt->get_result()->fetch_assoc();
    $surveysCountStmt->close();
    $totalSurveys = is_array($surveysCountRes) ? (int) $surveysCountRes['total'] : 0;

    $invitationsCountStmt = $connection->prepare('SELECT COUNT(*) AS total FROM survey_invitations');
    $invitationsCountStmt->execute();
    $invitationsCountRes = $invitationsCountStmt->get_result()->fetch_assoc();
    $invitationsCountStmt->close();
    $totalInvitations = is_array($invitationsCountRes) ? (int) $invitationsCountRes['total'] : 0;

    $completedRespStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM survey_invitations WHERE completed_at IS NOT NULL'
    );
    $completedRespStmt->execute();
    $completedRespRes = $completedRespStmt->get_result()->fetch_assoc();
    $completedRespStmt->close();
    $completedResponses = is_array($completedRespRes) ? (int) $completedRespRes['total'] : 0;

    if ($completedResponses === 0) {
        $respStmt = $connection->prepare('SELECT COUNT(*) AS total FROM survey_responses WHERE submitted_at IS NOT NULL');
        $respStmt->execute();
        $respRes = $respStmt->get_result()->fetch_assoc();
        $respStmt->close();
        $completedResponses = is_array($respRes) ? (int) $respRes['total'] : 0;
    }

    $completionRate = $totalInvitations > 0 ? (float) number_format(($completedResponses / $totalInvitations) * 100, 2) : 0.0;

    $surveyStats = [
        'total_surveys' => $totalSurveys,
        'total_invitations' => $totalInvitations,
        'completed_responses' => $completedResponses,
        'completion_rate' => $completionRate,
    ];

    // 4. Recommendation Request Statistics
    $recTotalStmt = $connection->prepare('SELECT COUNT(*) AS total FROM recommendation_requests');
    $recTotalStmt->execute();
    $recTotalRes = $recTotalStmt->get_result()->fetch_assoc();
    $recTotalStmt->close();
    $totalRecRequests = is_array($recTotalRes) ? (int) $recTotalRes['total'] : 0;

    $recPendingStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM recommendation_requests WHERE request_status = \'Pending\''
    );
    $recPendingStmt->execute();
    $recPendingRes = $recPendingStmt->get_result()->fetch_assoc();
    $recPendingStmt->close();
    $pendingRecRequests = is_array($recPendingRes) ? (int) $recPendingRes['total'] : 0;

    $recApprovedStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM recommendation_requests WHERE request_status = \'Approved\''
    );
    $recApprovedStmt->execute();
    $recApprovedRes = $recApprovedStmt->get_result()->fetch_assoc();
    $recApprovedStmt->close();
    $approvedRecRequests = is_array($recApprovedRes) ? (int) $recApprovedRes['total'] : 0;

    $recRejectedStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM recommendation_requests WHERE request_status = \'Rejected\''
    );
    $recRejectedStmt->execute();
    $recRejectedRes = $recRejectedStmt->get_result()->fetch_assoc();
    $recRejectedStmt->close();
    $rejectedRecRequests = is_array($recRejectedRes) ? (int) $recRejectedRes['total'] : 0;

    $recStats = [
        'total_requests' => $totalRecRequests,
        'pending_requests' => $pendingRecRequests,
        'approved_requests' => $approvedRecRequests,
        'rejected_requests' => $rejectedRecRequests,
    ];

    // 5. Upcoming Events Count
    $eventsStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM events WHERE is_active = 1 AND start_datetime >= NOW(6)'
    );
    $eventsStmt->execute();
    $eventsRes = $eventsStmt->get_result()->fetch_assoc();
    $eventsStmt->close();
    $upcomingEventsCount = is_array($eventsRes) ? (int) $eventsRes['total'] : 0;

    json_success(
        'Management dashboard overview retrieved successfully.',
        [
            'management_profile_id' => $managementProfileId,
            'total_graduates' => $totalGraduates,
            'employment' => $employmentStats,
            'employment_statistics' => $employmentStats,
            'surveys' => $surveyStats,
            'tracer_survey_statistics' => $surveyStats,
            'recommendations' => $recStats,
            'recommendation_request_statistics' => $recStats,
            'upcoming_events_count' => $upcomingEventsCount,
        ]
    );
}
