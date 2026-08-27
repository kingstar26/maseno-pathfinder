<?php

declare(strict_types=1);

/**
 * Ensures management_profile_id function is available if not defined in previous controller.
 */
if (!function_exists('management_profile_id')) {
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
}

/**
 * GET /management/reports/overview
 * Management Overview Statistics
 */
function get_management_reports_overview(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    $managementProfileId = management_profile_id($connection, $userId);

    // 1. Total Graduates
    $gradStmt = $connection->prepare('SELECT COUNT(*) AS total FROM graduate_profiles');
    $gradStmt->execute();
    $gradRes = $gradStmt->get_result()->fetch_assoc();
    $gradStmt->close();
    $totalGraduates = is_array($gradRes) ? (int) $gradRes['total'] : 0;

    if ($totalGraduates === 0) {
        $uGradStmt = $connection->prepare(
            'SELECT COUNT(*) AS total FROM users AS u
             INNER JOIN roles AS r ON r.role_id = u.role_id
             WHERE r.code = \'graduate\''
        );
        $uGradStmt->execute();
        $uGradRes = $uGradStmt->get_result()->fetch_assoc();
        $uGradStmt->close();
        $totalGraduates = is_array($uGradRes) ? (int) $uGradRes['total'] : 0;
    }

    // 2. Total Registered Users
    $userStmt = $connection->prepare('SELECT COUNT(*) AS total FROM users');
    $userStmt->execute();
    $userRes = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    $totalRegisteredUsers = is_array($userRes) ? (int) $userRes['total'] : 0;

    // 3. Total Employment Records
    $empRecordsStmt = $connection->prepare('SELECT COUNT(*) AS total FROM employment_records');
    $empRecordsStmt->execute();
    $empRecordsRes = $empRecordsStmt->get_result()->fetch_assoc();
    $empRecordsStmt->close();
    $totalEmploymentRecords = is_array($empRecordsRes) ? (int) $empRecordsRes['total'] : 0;

    // 4. Currently Employed Graduates
    $employedGradsStmt = $connection->prepare(
        'SELECT COUNT(DISTINCT er.graduate_profile_id) AS total
         FROM employment_records AS er
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         WHERE es.code IN (\'EMPLOYED\', \'SELF_EMPLOYED\')'
    );
    $employedGradsStmt->execute();
    $employedGradsRes = $employedGradsStmt->get_result()->fetch_assoc();
    $employedGradsStmt->close();
    $currentlyEmployed = is_array($employedGradsRes) ? (int) $employedGradsRes['total'] : 0;

    // 5. Self-Employed Graduates
    $selfEmployedStmt = $connection->prepare(
        'SELECT COUNT(DISTINCT er.graduate_profile_id) AS total
         FROM employment_records AS er
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         WHERE es.code = \'SELF_EMPLOYED\''
    );
    $selfEmployedStmt->execute();
    $selfEmployedRes = $selfEmployedStmt->get_result()->fetch_assoc();
    $selfEmployedStmt->close();
    $selfEmployed = is_array($selfEmployedRes) ? (int) $selfEmployedRes['total'] : 0;

    if ($selfEmployed === 0) {
        $bizGradsStmt = $connection->prepare('SELECT COUNT(DISTINCT graduate_profile_id) AS total FROM businesses');
        $bizGradsStmt->execute();
        $bizGradsRes = $bizGradsStmt->get_result()->fetch_assoc();
        $bizGradsStmt->close();
        $bizGrads = is_array($bizGradsRes) ? (int) $bizGradsRes['total'] : 0;
        if ($bizGrads > 0) {
            $selfEmployed = $bizGrads;
        }
    }

    // 5.5 Further Studies Graduates
    $furtherStudiesStmt = $connection->prepare(
        'SELECT COUNT(DISTINCT er.graduate_profile_id) AS total
         FROM employment_records AS er
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         WHERE es.code = \'FURTHER_STUDIES\''
    );
    $furtherStudiesStmt->execute();
    $furtherStudiesRes = $furtherStudiesStmt->get_result()->fetch_assoc();
    $furtherStudiesStmt->close();
    $furtherStudiesGraduates = is_array($furtherStudiesRes) ? (int) $furtherStudiesRes['total'] : 0;

    // 6. Unemployed Graduates
    $unemployedStmt = $connection->prepare(
        'SELECT COUNT(DISTINCT er.graduate_profile_id) AS total
         FROM employment_records AS er
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         WHERE es.code = \'UNEMPLOYED\''
    );
    $unemployedStmt->execute();
    $unemployedRes = $unemployedStmt->get_result()->fetch_assoc();
    $unemployedStmt->close();
    $unemployed = is_array($unemployedRes) ? (int) $unemployedRes['total'] : 0;

    // 7. Total Businesses
    $bizStmt = $connection->prepare('SELECT COUNT(*) AS total FROM businesses');
    $bizStmt->execute();
    $bizRes = $bizStmt->get_result()->fetch_assoc();
    $bizStmt->close();
    $totalBusinesses = is_array($bizRes) ? (int) $bizRes['total'] : 0;

    // 8. Total Surveys
    $surveysStmt = $connection->prepare('SELECT COUNT(*) AS total FROM surveys');
    $surveysStmt->execute();
    $surveysRes = $surveysStmt->get_result()->fetch_assoc();
    $surveysStmt->close();
    $totalSurveys = is_array($surveysRes) ? (int) $surveysRes['total'] : 0;

    // 9. Completed Surveys & Total Invitations
    $invStmt = $connection->prepare('SELECT COUNT(*) AS total FROM survey_invitations');
    $invStmt->execute();
    $invRes = $invStmt->get_result()->fetch_assoc();
    $invStmt->close();
    $totalInvitations = is_array($invRes) ? (int) $invRes['total'] : 0;

    $compSurveysStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM survey_invitations AS si
         LEFT JOIN survey_response_statuses AS srs ON srs.survey_response_status_id = si.survey_response_status_id
         WHERE srs.code = \'SUBMITTED\' OR si.completed_at IS NOT NULL'
    );
    $compSurveysStmt->execute();
    $compSurveysRes = $compSurveysStmt->get_result()->fetch_assoc();
    $compSurveysStmt->close();
    $completedSurveys = is_array($compSurveysRes) ? (int) $compSurveysRes['total'] : 0;

    if ($completedSurveys === 0) {
        $respStmt = $connection->prepare('SELECT COUNT(*) AS total FROM survey_responses WHERE submitted_at IS NOT NULL');
        $respStmt->execute();
        $respRes = $respStmt->get_result()->fetch_assoc();
        $respStmt->close();
        $completedSurveys = is_array($respRes) ? (int) $respRes['total'] : 0;
    }

    $surveyDenom = $totalInvitations > 0 ? $totalInvitations : ($totalSurveys > 0 ? $totalSurveys : 1);
    $surveyCompletionPercentage = round(($completedSurveys / $surveyDenom) * 100, 2);

    // 10. Recommendations Statistics
    $recTotalStmt = $connection->prepare('SELECT COUNT(*) AS total FROM recommendation_requests');
    $recTotalStmt->execute();
    $recTotalRes = $recTotalStmt->get_result()->fetch_assoc();
    $recTotalStmt->close();
    $totalRecommendations = is_array($recTotalRes) ? (int) $recTotalRes['total'] : 0;

    $recApprStmt = $connection->prepare('SELECT COUNT(*) AS total FROM recommendation_requests WHERE request_status = \'Approved\'');
    $recApprStmt->execute();
    $recApprRes = $recApprStmt->get_result()->fetch_assoc();
    $recApprStmt->close();
    $approvedRecommendations = is_array($recApprRes) ? (int) $recApprRes['total'] : 0;

    $recPendStmt = $connection->prepare('SELECT COUNT(*) AS total FROM recommendation_requests WHERE request_status = \'Pending\'');
    $recPendStmt->execute();
    $recPendRes = $recPendStmt->get_result()->fetch_assoc();
    $recPendStmt->close();
    $pendingRecommendations = is_array($recPendRes) ? (int) $recPendRes['total'] : 0;

    $recRejStmt = $connection->prepare('SELECT COUNT(*) AS total FROM recommendation_requests WHERE request_status = \'Rejected\'');
    $recRejStmt->execute();
    $recRejRes = $recRejStmt->get_result()->fetch_assoc();
    $recRejStmt->close();
    $rejectedRecommendations = is_array($recRejRes) ? (int) $recRejRes['total'] : 0;

    // 11. Events Statistics
    $eventsStmt = $connection->prepare('SELECT COUNT(*) AS total FROM events');
    $eventsStmt->execute();
    $eventsRes = $eventsStmt->get_result()->fetch_assoc();
    $eventsStmt->close();
    $totalEvents = is_array($eventsRes) ? (int) $eventsRes['total'] : 0;

    $eventRegsStmt = $connection->prepare('SELECT COUNT(*) AS total FROM event_registrations');
    $eventRegsStmt->execute();
    $eventRegsRes = $eventRegsStmt->get_result()->fetch_assoc();
    $eventRegsStmt->close();
    $totalEventRegistrations = is_array($eventRegsRes) ? (int) $eventRegsRes['total'] : 0;

    $employmentRate = round(($currentlyEmployed / ($totalGraduates > 0 ? $totalGraduates : 1)) * 100, 2);

    $data = [
        'graduates' => $totalGraduates,
        'total_graduates' => $totalGraduates,
        'total_registered_users' => $totalRegisteredUsers,
        'total_employment_records' => $totalEmploymentRecords,
        'currently_employed_graduates' => $currentlyEmployed,
        'employed_graduates' => $currentlyEmployed,
        'employed_count' => $currentlyEmployed,
        'self_employed_graduates' => $selfEmployed,
        'self_employed_count' => $selfEmployed,
        'further_studies_graduates' => $furtherStudiesGraduates,
        'further_studies_count' => $furtherStudiesGraduates,
        'unemployed_graduates' => $unemployed,
        'unemployed_count' => $unemployed,
        'employment_rate' => $employmentRate,
        'total_businesses' => $totalBusinesses,
        'businesses' => $totalBusinesses,
        'total_surveys' => $totalSurveys,
        'surveys' => $totalSurveys,
        'completed_surveys' => $completedSurveys,
        'survey_completion_percentage' => $surveyCompletionPercentage,
        'survey_completion_rate' => $surveyCompletionPercentage,
        'total_recommendation_requests' => $totalRecommendations,
        'approved_recommendations' => $approvedRecommendations,
        'pending_recommendations' => $pendingRecommendations,
        'rejected_recommendations' => $rejectedRecommendations,
        'recommendations' => [
            'total' => $totalRecommendations,
            'approved' => $approvedRecommendations,
            'pending' => $pendingRecommendations,
            'rejected' => $rejectedRecommendations,
        ],
        'total_events' => $totalEvents,
        'events' => $totalEvents,
        'total_event_registrations' => $totalEventRegistrations,
    ];

    json_success('Management overview statistics fetched successfully.', $data);
}

/**
 * GET /management/reports/employment
 * Employment Analytics
 */
function get_management_reports_employment(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    management_profile_id($connection, $userId);

    // 1. Employment status breakdown
    $statusStmt = $connection->prepare(
        'SELECT 
            es.employment_status_id,
            es.name AS status,
            es.code,
            COUNT(er.employment_record_id) AS count
         FROM employment_statuses AS es
         LEFT JOIN employment_records AS er ON er.employment_status_id = es.employment_status_id
         GROUP BY es.employment_status_id, es.name, es.code
         ORDER BY count DESC, es.name ASC'
    );
    $statusStmt->execute();
    $rawStatus = $statusStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $statusStmt->close();

    $totalEmpRecords = 0;
    $employedCount = 0;
    $selfEmployedCount = 0;
    $furtherStudiesCount = 0;
    $unemployedCount = 0;

    foreach ($rawStatus as $stRow) {
        $cnt = (int) $stRow['count'];
        $totalEmpRecords += $cnt;
        $code = (string) $stRow['code'];
        if ($code === 'EMPLOYED') {
            $employedCount = $cnt;
        } elseif ($code === 'SELF_EMPLOYED') {
            $selfEmployedCount = $cnt;
        } elseif ($code === 'FURTHER_STUDIES') {
            $furtherStudiesCount = $cnt;
        } elseif ($code === 'UNEMPLOYED') {
            $unemployedCount = $cnt;
        }
    }

    $statusBreakdown = [];
    foreach ($rawStatus as $stRow) {
        $cnt = (int) $stRow['count'];
        $pct = $totalEmpRecords > 0 ? round(($cnt / $totalEmpRecords) * 100, 2) : 0.0;
        $statusBreakdown[] = [
            'employment_status_id' => (int) $stRow['employment_status_id'],
            'status' => (string) $stRow['status'],
            'code' => (string) $stRow['code'],
            'count' => $cnt,
            'percentage' => $pct,
        ];
    }

    // Total graduates & employed graduates for percentage calculation
    $gradStmt = $connection->prepare('SELECT COUNT(*) AS total FROM graduate_profiles');
    $gradStmt->execute();
    $gradRes = $gradStmt->get_result()->fetch_assoc();
    $gradStmt->close();
    $totalGraduates = is_array($gradRes) ? (int) $gradRes['total'] : 0;

    $empGradStmt = $connection->prepare(
        'SELECT COUNT(DISTINCT er.graduate_profile_id) AS total
         FROM employment_records AS er
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         WHERE es.code IN (\'EMPLOYED\', \'SELF_EMPLOYED\')'
    );
    $empGradStmt->execute();
    $empGradRes = $empGradStmt->get_result()->fetch_assoc();
    $empGradStmt->close();
    $employedGraduates = is_array($empGradRes) ? (int) $empGradRes['total'] : 0;

    $employmentPercentage = round(($employedGraduates / ($totalGraduates > 0 ? $totalGraduates : 1)) * 100, 2);

    // 2. Employment by Faculty
    $facultyStmt = $connection->prepare(
        'SELECT 
            f.faculty_id,
            f.name AS faculty,
            COUNT(DISTINCT gq.graduate_profile_id) AS graduates,
            COUNT(DISTINCT CASE WHEN er.employment_record_id IS NOT NULL AND es.code IN (\'EMPLOYED\', \'SELF_EMPLOYED\') THEN gq.graduate_profile_id END) AS employed
         FROM faculties AS f
         LEFT JOIN departments AS d ON d.faculty_id = f.faculty_id
         LEFT JOIN programmes AS p ON p.department_id = d.department_id
         LEFT JOIN graduate_qualifications AS gq ON gq.programme_id = p.programme_id
         LEFT JOIN employment_records AS er ON er.graduate_profile_id = gq.graduate_profile_id
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         GROUP BY f.faculty_id, f.name
         ORDER BY f.name ASC'
    );
    $facultyStmt->execute();
    $rawFaculty = $facultyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $facultyStmt->close();

    $facultyStatistics = [];
    foreach ($rawFaculty as $facRow) {
        $grads = (int) $facRow['graduates'];
        $emp = (int) $facRow['employed'];
        $rate = $grads > 0 ? round(($emp / $grads) * 100, 2) : 0.0;
        $facultyStatistics[] = [
            'faculty_id' => (int) $facRow['faculty_id'],
            'faculty' => (string) $facRow['faculty'],
            'graduates' => $grads,
            'employed' => $emp,
            'rate' => $rate,
        ];
    }

    usort($facultyStatistics, function ($a, $b) {
        if ($a['graduates'] !== $b['graduates']) {
            return $b['graduates'] <=> $a['graduates'];
        }
        if ($a['rate'] !== $b['rate']) {
            return $b['rate'] <=> $a['rate'];
        }
        return strcmp($a['faculty'], $b['faculty']);
    });

    // 3. Employment by Programme
    $progStmt = $connection->prepare(
        'SELECT 
            p.programme_id,
            p.name AS programme,
            f.name AS faculty,
            COUNT(DISTINCT gq.graduate_profile_id) AS graduates,
            COUNT(DISTINCT CASE WHEN er.employment_record_id IS NOT NULL AND es.code IN (\'EMPLOYED\', \'SELF_EMPLOYED\') THEN gq.graduate_profile_id END) AS employed
         FROM programmes AS p
         INNER JOIN departments AS d ON d.department_id = p.department_id
         INNER JOIN faculties AS f ON f.faculty_id = d.faculty_id
         LEFT JOIN graduate_qualifications AS gq ON gq.programme_id = p.programme_id
         LEFT JOIN employment_records AS er ON er.graduate_profile_id = gq.graduate_profile_id
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         GROUP BY p.programme_id, p.name, f.name
         ORDER BY p.name ASC'
    );
    $progStmt->execute();
    $rawProg = $progStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $progStmt->close();

    $programmeStatistics = [];
    foreach ($rawProg as $progRow) {
        $grads = (int) $progRow['graduates'];
        $emp = (int) $progRow['employed'];
        $rate = $grads > 0 ? round(($emp / $grads) * 100, 2) : 0.0;
        $programmeStatistics[] = [
            'programme_id' => (int) $progRow['programme_id'],
            'programme' => (string) $progRow['programme'],
            'faculty' => (string) $progRow['faculty'],
            'graduates' => $grads,
            'employed' => $emp,
            'rate' => $rate,
        ];
    }

    usort($programmeStatistics, function ($a, $b) {
        if ($a['graduates'] !== $b['graduates']) {
            return $b['graduates'] <=> $a['graduates'];
        }
        if ($a['rate'] !== $b['rate']) {
            return $b['rate'] <=> $a['rate'];
        }
        return strcmp($a['programme'], $b['programme']);
    });

    $data = [
        'total_employment_records' => $totalEmpRecords,
        'employed_count' => $employedCount,
        'currently_employed_graduates' => $employedCount,
        'employed_graduates' => $employedCount,
        'self_employed_count' => $selfEmployedCount,
        'self_employed_graduates' => $selfEmployedCount,
        'further_studies_count' => $furtherStudiesCount,
        'further_studies_graduates' => $furtherStudiesCount,
        'unemployed_count' => $unemployedCount,
        'unemployed_graduates' => $unemployedCount,
        'status_breakdown' => $statusBreakdown,
        'employment_percentage' => $employmentPercentage,
        'employment_rate' => $employmentPercentage,
        'employment_by_faculty' => $facultyStatistics,
        'faculty_statistics' => $facultyStatistics,
        'employment_by_programme' => $programmeStatistics,
        'programme_statistics' => $programmeStatistics,
    ];

    json_success('Employment analytics fetched successfully.', $data);
}

/**
 * GET /management/reports/graduates
 * Graduate Distribution Report
 */
function get_management_reports_graduates(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    management_profile_id($connection, $userId);

    // Total graduates count for percentage calculation
    $totStmt = $connection->prepare('SELECT COUNT(*) AS total FROM graduate_profiles');
    $totStmt->execute();
    $totRes = $totStmt->get_result()->fetch_assoc();
    $totStmt->close();
    $totalGraduates = is_array($totRes) ? (int) $totRes['total'] : 0;
    $totalGradsDenom = $totalGraduates > 0 ? $totalGraduates : 1;

    // 1. Distribution by Faculty
    $facStmt = $connection->prepare(
        'SELECT 
            f.faculty_id,
            f.name AS faculty,
            COUNT(DISTINCT gq.graduate_profile_id) AS count
         FROM faculties AS f
         LEFT JOIN departments AS d ON d.faculty_id = f.faculty_id
         LEFT JOIN programmes AS p ON p.department_id = d.department_id
         LEFT JOIN graduate_qualifications AS gq ON gq.programme_id = p.programme_id
         GROUP BY f.faculty_id, f.name
         ORDER BY count DESC, f.name ASC'
    );
    $facStmt->execute();
    $rawFac = $facStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $facStmt->close();

    $byFaculty = [];
    foreach ($rawFac as $row) {
        $cnt = (int) $row['count'];
        $byFaculty[] = [
            'faculty_id' => (int) $row['faculty_id'],
            'faculty' => (string) $row['faculty'],
            'count' => $cnt,
            'percentage' => round(($cnt / $totalGradsDenom) * 100, 2),
        ];
    }

    // 2. Distribution by Programme
    $progStmt = $connection->prepare(
        'SELECT 
            p.programme_id,
            p.name AS programme,
            f.name AS faculty,
            COUNT(DISTINCT gq.graduate_profile_id) AS count
         FROM programmes AS p
         INNER JOIN departments AS d ON d.department_id = p.department_id
         INNER JOIN faculties AS f ON f.faculty_id = d.faculty_id
         LEFT JOIN graduate_qualifications AS gq ON gq.programme_id = p.programme_id
         GROUP BY p.programme_id, p.name, f.name
         ORDER BY count DESC, p.name ASC'
    );
    $progStmt->execute();
    $rawProg = $progStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $progStmt->close();

    $byProgramme = [];
    foreach ($rawProg as $row) {
        $cnt = (int) $row['count'];
        $byProgramme[] = [
            'programme_id' => (int) $row['programme_id'],
            'programme' => (string) $row['programme'],
            'faculty' => (string) $row['faculty'],
            'count' => $cnt,
            'percentage' => round(($cnt / $totalGradsDenom) * 100, 2),
        ];
    }

    // 3. Distribution by Graduation Year
    $yrStmt = $connection->prepare(
        'SELECT 
            gq.year_of_graduation AS year,
            COUNT(DISTINCT gq.graduate_profile_id) AS count
         FROM graduate_qualifications AS gq
         WHERE gq.year_of_graduation IS NOT NULL
         GROUP BY gq.year_of_graduation
         ORDER BY gq.year_of_graduation DESC'
    );
    $yrStmt->execute();
    $rawYr = $yrStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $yrStmt->close();

    $byYear = [];
    foreach ($rawYr as $row) {
        $cnt = (int) $row['count'];
        $byYear[] = [
            'year' => (int) $row['year'],
            'graduation_year' => (int) $row['year'],
            'count' => $cnt,
            'percentage' => round(($cnt / $totalGradsDenom) * 100, 2),
        ];
    }

    // 4. Distribution by County
    $ctyStmt = $connection->prepare(
        'SELECT 
            gp.county,
            COUNT(DISTINCT gp.graduate_profile_id) AS count
         FROM graduate_profiles AS gp
         WHERE gp.county IS NOT NULL AND TRIM(gp.county) != \'\'
         GROUP BY gp.county
         ORDER BY count DESC, gp.county ASC'
    );
    $ctyStmt->execute();
    $rawCty = $ctyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ctyStmt->close();

    $byCounty = [];
    foreach ($rawCty as $row) {
        $cnt = (int) $row['count'];
        $byCounty[] = [
            'county' => (string) $row['county'],
            'count' => $cnt,
            'percentage' => round(($cnt / $totalGradsDenom) * 100, 2),
        ];
    }

    $graduateStmt = $connection->prepare(
        'SELECT
            gp.graduate_profile_id,
            u.user_id,
            u.first_name,
            u.last_name,
            gq.admission_number,
            f.name AS faculty,
            p.name AS programme,
            gq.year_of_graduation,
            es.name AS employment_status,
            rr.request_status AS recommendation_status
         FROM graduate_profiles AS gp
         INNER JOIN users AS u ON u.user_id = gp.user_id
         LEFT JOIN graduate_qualifications AS gq ON gq.graduate_qualification_id = (
             SELECT latest_gq.graduate_qualification_id
             FROM graduate_qualifications AS latest_gq
             WHERE latest_gq.graduate_profile_id = gp.graduate_profile_id
             ORDER BY latest_gq.updated_at DESC, latest_gq.graduate_qualification_id DESC
             LIMIT 1
         )
         LEFT JOIN programmes AS p ON p.programme_id = gq.programme_id
         LEFT JOIN departments AS d ON d.department_id = p.department_id
         LEFT JOIN faculties AS f ON f.faculty_id = d.faculty_id
         LEFT JOIN employment_records AS er ON er.employment_record_id = (
             SELECT latest_er.employment_record_id
             FROM employment_records AS latest_er
             WHERE latest_er.graduate_profile_id = gp.graduate_profile_id
             ORDER BY latest_er.updated_at DESC, latest_er.employment_record_id DESC
             LIMIT 1
         )
         LEFT JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
         LEFT JOIN recommendation_requests AS rr ON rr.recommendation_request_id = (
             SELECT latest_rr.recommendation_request_id
             FROM recommendation_requests AS latest_rr
             WHERE latest_rr.graduate_profile_id = gp.graduate_profile_id
             ORDER BY latest_rr.requested_at DESC, latest_rr.recommendation_request_id DESC
             LIMIT 1
         )
         ORDER BY u.first_name ASC, u.last_name ASC, gp.graduate_profile_id ASC'
    );
    $graduateStmt->execute();
    $graduates = $graduateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $graduateStmt->close();

    $data = [
        'graduates' => $graduates,
        'by_faculty' => $byFaculty,
        'faculty_distribution' => $byFaculty,
        'by_programme' => $byProgramme,
        'programme_distribution' => $byProgramme,
        'by_graduation_year' => $byYear,
        'by_year' => $byYear,
        'graduation_year_distribution' => $byYear,
        'by_county' => $byCounty,
        'county_distribution' => $byCounty,
    ];

    json_success('Graduate distribution report fetched successfully.', $data);
}

/**
 * GET /management/reports/surveys
 * Survey Analytics
 */
function get_management_reports_surveys(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    management_profile_id($connection, $userId);

    // Total Available Surveys
    $survCntStmt = $connection->prepare('SELECT COUNT(*) AS total FROM surveys');
    $survCntStmt->execute();
    $survCntRes = $survCntStmt->get_result()->fetch_assoc();
    $survCntStmt->close();
    $totalSurveys = is_array($survCntRes) ? (int) $survCntRes['total'] : 0;

    // Total Invitations Sent
    $invStmt = $connection->prepare('SELECT COUNT(*) AS total FROM survey_invitations');
    $invStmt->execute();
    $invRes = $invStmt->get_result()->fetch_assoc();
    $invStmt->close();
    $totalInvitations = is_array($invRes) ? (int) $invRes['total'] : 0;

    // Completed Responses
    $compStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM survey_invitations AS si
         LEFT JOIN survey_response_statuses AS srs ON srs.survey_response_status_id = si.survey_response_status_id
         WHERE srs.code = \'SUBMITTED\' OR si.completed_at IS NOT NULL'
    );
    $compStmt->execute();
    $compRes = $compStmt->get_result()->fetch_assoc();
    $compStmt->close();
    $completedResponses = is_array($compRes) ? (int) $compRes['total'] : 0;

    if ($completedResponses === 0) {
        $respCntStmt = $connection->prepare('SELECT COUNT(*) AS total FROM survey_responses WHERE submitted_at IS NOT NULL');
        $respCntStmt->execute();
        $respCntRes = $respCntStmt->get_result()->fetch_assoc();
        $respCntStmt->close();
        $completedResponses = is_array($respCntRes) ? (int) $respCntRes['total'] : 0;
    }

    $surveyDenom = $totalInvitations > 0 ? $totalInvitations : ($totalSurveys > 0 ? $totalSurveys : 1);
    $overallCompletionRate = round(($completedResponses / $surveyDenom) * 100, 2);

    // List per survey with details
    $survListStmt = $connection->prepare(
        'SELECT 
            s.survey_id,
            s.name AS title,
            s.name,
            s.is_active,
            s.available_from,
            s.available_until,
            COUNT(DISTINCT si.survey_invitation_id) AS total_invited,
            COUNT(DISTINCT CASE WHEN srs.code = \'SUBMITTED\' OR si.completed_at IS NOT NULL THEN si.survey_invitation_id END) AS total_completed
         FROM surveys AS s
         LEFT JOIN survey_invitations AS si ON si.survey_id = s.survey_id
         LEFT JOIN survey_response_statuses AS srs ON srs.survey_response_status_id = si.survey_response_status_id
         GROUP BY s.survey_id, s.name, s.is_active, s.available_from, s.available_until
         ORDER BY s.created_at DESC'
    );
    $survListStmt->execute();
    $rawSurveys = $survListStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $survListStmt->close();

    $surveyDetailsList = [];
    foreach ($rawSurveys as $sRow) {
        $surveyId = (int) $sRow['survey_id'];
        $invited = (int) $sRow['total_invited'];
        $completed = (int) $sRow['total_completed'];
        $denom = $invited > 0 ? $invited : 1;
        $compRate = round(($completed / $denom) * 100, 2);

        // Fetch questions for this survey
        $qStmt = $connection->prepare(
            'SELECT 
                sq.survey_question_id,
                sq.prompt,
                sq.response_type,
                sq.is_required,
                sq.display_order,
                sec.title AS section_title
             FROM survey_questions AS sq
             INNER JOIN survey_sections AS sec ON sec.survey_section_id = sq.survey_section_id
             WHERE sec.survey_id = ?
             ORDER BY sec.display_order ASC, sq.display_order ASC'
        );
        $qStmt->bind_param('i', $surveyId);
        $qStmt->execute();
        $rawQuestions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $qStmt->close();

        $questionsAnalytics = [];
        foreach ($rawQuestions as $qRow) {
            $questionId = (int) $qRow['survey_question_id'];

            // Count total answers for this question
            $ansCntStmt = $connection->prepare('SELECT COUNT(*) AS total FROM survey_answers WHERE survey_question_id = ?');
            $ansCntStmt->bind_param('i', $questionId);
            $ansCntStmt->execute();
            $ansCntRes = $ansCntStmt->get_result()->fetch_assoc();
            $ansCntStmt->close();
            $totalAnswers = is_array($ansCntRes) ? (int) $ansCntRes['total'] : 0;

            // Fetch option breakdown if any
            $optStmt = $connection->prepare(
                'SELECT 
                    sqo.survey_question_option_id,
                    sqo.label,
                    sqo.value,
                    COUNT(sa.survey_answer_id) AS count
                 FROM survey_question_options AS sqo
                 LEFT JOIN survey_answers AS sa ON sa.survey_question_option_id = sqo.survey_question_option_id
                 WHERE sqo.survey_question_id = ?
                 GROUP BY sqo.survey_question_option_id, sqo.label, sqo.value, sqo.display_order
                 ORDER BY sqo.display_order ASC'
            );
            $optStmt->bind_param('i', $questionId);
            $optStmt->execute();
            $rawOptions = $optStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $optStmt->close();

            $optionBreakdown = [];
            $ansDenom = $totalAnswers > 0 ? $totalAnswers : 1;
            foreach ($rawOptions as $optRow) {
                $oCount = (int) $optRow['count'];
                $optionBreakdown[] = [
                    'survey_question_option_id' => (int) $optRow['survey_question_option_id'],
                    'label' => (string) $optRow['label'],
                    'value' => (string) $optRow['value'],
                    'count' => $oCount,
                    'percentage' => round(($oCount / $ansDenom) * 100, 2),
                ];
            }

            $questionsAnalytics[] = [
                'survey_question_id' => $questionId,
                'prompt' => (string) $qRow['prompt'],
                'response_type' => (string) $qRow['response_type'],
                'section_title' => (string) $qRow['section_title'],
                'total_responses' => $totalAnswers,
                'option_breakdown' => $optionBreakdown,
            ];
        }

        $surveyDetailsList[] = [
            'survey_id' => $surveyId,
            'title' => (string) $sRow['title'],
            'name' => (string) $sRow['name'],
            'is_active' => (int) $sRow['is_active'],
            'available_from' => $sRow['available_from'] !== null ? (string) $sRow['available_from'] : null,
            'available_until' => $sRow['available_until'] !== null ? (string) $sRow['available_until'] : null,
            'total_invited' => $invited,
            'total_completed' => $completed,
            'completion_rate' => $compRate,
            'completion_percentage' => $compRate,
            'questions' => $questionsAnalytics,
        ];
    }

    $data = [
        'available_surveys' => $totalSurveys,
        'total_surveys' => $totalSurveys,
        'invitations_sent' => $totalInvitations,
        'total_invited' => $totalInvitations,
        'completed_responses' => $completedResponses,
        'total_completed' => $completedResponses,
        'completion_percentage' => $overallCompletionRate,
        'completion_rate' => $overallCompletionRate,
        'surveys' => $surveyDetailsList,
    ];

    json_success('Survey analytics fetched successfully.', $data);
}

/**
 * GET /management/reports/events
 * Event Analytics
 */
function get_management_reports_events(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    management_profile_id($connection, $userId);

    // 1. Overall event metrics
    $totEventsStmt = $connection->prepare('SELECT COUNT(*) AS total FROM events');
    $totEventsStmt->execute();
    $totEventsRes = $totEventsStmt->get_result()->fetch_assoc();
    $totEventsStmt->close();
    $totalEvents = is_array($totEventsRes) ? (int) $totEventsRes['total'] : 0;

    $pubEventsStmt = $connection->prepare('SELECT COUNT(*) AS total FROM events WHERE is_active = 1');
    $pubEventsStmt->execute();
    $pubEventsRes = $pubEventsStmt->get_result()->fetch_assoc();
    $pubEventsStmt->close();
    $publishedEvents = is_array($pubEventsRes) ? (int) $pubEventsRes['total'] : 0;

    $upcEventsStmt = $connection->prepare('SELECT COUNT(*) AS total FROM events WHERE start_datetime >= NOW()');
    $upcEventsStmt->execute();
    $upcEventsRes = $upcEventsStmt->get_result()->fetch_assoc();
    $upcEventsStmt->close();
    $upcomingEvents = is_array($upcEventsRes) ? (int) $upcEventsRes['total'] : 0;

    $regStmt = $connection->prepare('SELECT COUNT(*) AS total FROM event_registrations');
    $regStmt->execute();
    $regRes = $regStmt->get_result()->fetch_assoc();
    $regStmt->close();
    $totalRegistrations = is_array($regRes) ? (int) $regRes['total'] : 0;

    $attStmt = $connection->prepare('SELECT COUNT(*) AS total FROM event_registrations WHERE attended = 1');
    $attStmt->execute();
    $attRes = $attStmt->get_result()->fetch_assoc();
    $attStmt->close();
    $totalAttended = is_array($attRes) ? (int) $attRes['total'] : 0;

    $regDenom = $totalRegistrations > 0 ? $totalRegistrations : 1;
    $overallAttendanceRate = round(($totalAttended / $regDenom) * 100, 2);

    // 2. Breakdown per event
    $evListStmt = $connection->prepare(
        'SELECT 
            e.event_id,
            e.title,
            e.event_type,
            e.venue,
            e.start_datetime,
            e.end_datetime,
            e.max_attendees,
            e.is_active,
            COUNT(er.event_registration_id) AS total_registered,
            SUM(CASE WHEN er.attended = 1 THEN 1 ELSE 0 END) AS total_attended
         FROM events AS e
         LEFT JOIN event_registrations AS er ON er.event_id = e.event_id
         GROUP BY e.event_id, e.title, e.event_type, e.venue, e.start_datetime, e.end_datetime, e.max_attendees, e.is_active
         ORDER BY e.start_datetime DESC'
    );
    $evListStmt->execute();
    $rawEvents = $evListStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $evListStmt->close();

    $eventsList = [];
    foreach ($rawEvents as $eRow) {
        $regCnt = (int) $eRow['total_registered'];
        $attCnt = (int) $eRow['total_attended'];
        $eDenom = $regCnt > 0 ? $regCnt : 1;
        $attRate = round(($attCnt / $eDenom) * 100, 2);

        $eventsList[] = [
            'event_id' => (int) $eRow['event_id'],
            'title' => (string) $eRow['title'],
            'event_type' => $eRow['event_type'] !== null ? (string) $eRow['event_type'] : null,
            'venue' => $eRow['venue'] !== null ? (string) $eRow['venue'] : null,
            'start_datetime' => (string) $eRow['start_datetime'],
            'end_datetime' => $eRow['end_datetime'] !== null ? (string) $eRow['end_datetime'] : null,
            'max_attendees' => $eRow['max_attendees'] !== null ? (int) $eRow['max_attendees'] : null,
            'is_active' => (int) $eRow['is_active'],
            'registration_count' => $regCnt,
            'registrations_count' => $regCnt,
            'attended_count' => $attCnt,
            'attendance_rate' => $attRate,
        ];
    }

    $data = [
        'total_events' => $totalEvents,
        'published_events' => $publishedEvents,
        'upcoming_events' => $upcomingEvents,
        'registration_counts' => $totalRegistrations,
        'total_registrations' => $totalRegistrations,
        'total_attended' => $totalAttended,
        'attendance_statistics' => [
            'total_registrations' => $totalRegistrations,
            'total_attended' => $totalAttended,
            'overall_attendance_rate' => $overallAttendanceRate,
        ],
        'overall_attendance_rate' => $overallAttendanceRate,
        'events' => $eventsList,
    ];

    json_success('Event analytics fetched successfully.', $data);
}

/**
 * GET /management/reports/recommendations
 * Recommendation Analytics
 */
function get_management_reports_recommendations(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    management_profile_id($connection, $userId);

    $recStmt = $connection->prepare(
        'SELECT 
            COUNT(*) AS total_requests,
            SUM(CASE WHEN request_status = \'Pending\' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN request_status = \'Approved\' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN request_status = \'Rejected\' THEN 1 ELSE 0 END) AS rejected,
            AVG(CASE WHEN processed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, requested_at, processed_at) END) AS avg_processing_minutes
         FROM recommendation_requests'
    );
    $recStmt->execute();
    $recRes = $recStmt->get_result()->fetch_assoc();
    $recStmt->close();

    $totalRequests = is_array($recRes) ? (int) $recRes['total_requests'] : 0;
    $pending = is_array($recRes) ? (int) $recRes['pending'] : 0;
    $approved = is_array($recRes) ? (int) $recRes['approved'] : 0;
    $rejected = is_array($recRes) ? (int) $recRes['rejected'] : 0;
    $avgMinutes = (is_array($recRes) && $recRes['avg_processing_minutes'] !== null) ? (float) $recRes['avg_processing_minutes'] : 0.0;
    $avgHours = round($avgMinutes / 60, 2);

    $data = [
        'total_requests' => $totalRequests,
        'total' => $totalRequests,
        'pending' => $pending,
        'approved' => $approved,
        'rejected' => $rejected,
        'average_processing_time_hours' => $avgHours,
        'average_processing_time' => $avgHours . ' hours',
    ];

    json_success('Recommendation analytics fetched successfully.', $data);
}

/**
 * GET /management/reports/export
 * Export Reports to CSV
 */
function export_management_reports(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    $userId = (int) $authenticated['user_id'];
    management_profile_id($connection, $userId);

    $type = isset($_GET['type']) ? strtolower(trim((string) $_GET['type'])) : 'employment';
    if (!in_array($type, ['employment', 'graduates', 'surveys', 'events'], true)) {
        json_error('Invalid export type specified. Valid types are: employment, graduates, surveys, events.', [], 400);
    }

    $timestamp = date('Ymd_His');
    $filename = "maseno_report_{$type}_{$timestamp}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        json_error('Failed to generate CSV export stream.', [], 500);
    }

    if ($type === 'employment') {
        fputcsv($output, [
            'Graduate Name',
            'Email',
            'County',
            'Programme',
            'Faculty',
            'Company Name',
            'Job Title',
            'Employment Status',
            'Industry',
            'Employment Type',
            'Current Job',
            'Start Date',
        ]);

        $query = 'SELECT 
                    CONCAT(u.first_name, \' \', u.last_name) AS graduate_name,
                    u.email,
                    gp.county,
                    p.name AS programme_name,
                    f.name AS faculty_name,
                    er.company_name,
                    er.job_title,
                    es.name AS employment_status,
                    er.industry,
                    er.employment_type,
                    CASE WHEN er.current_job = 1 THEN \'Yes\' ELSE \'No\' END AS is_current_job,
                    er.start_date
                  FROM employment_records er
                  INNER JOIN graduate_profiles gp ON gp.graduate_profile_id = er.graduate_profile_id
                  INNER JOIN users u ON u.user_id = gp.user_id
                  LEFT JOIN employment_statuses es ON es.employment_status_id = er.employment_status_id
                  LEFT JOIN graduate_qualifications gq ON gq.graduate_profile_id = gp.graduate_profile_id
                  LEFT JOIN programmes p ON p.programme_id = gq.programme_id
                  LEFT JOIN departments d ON d.department_id = p.department_id
                  LEFT JOIN faculties f ON f.faculty_id = d.faculty_id
                  ORDER BY er.created_at DESC';
        
        $result = $connection->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['graduate_name'] ?? '',
                    $row['email'] ?? '',
                    $row['county'] ?? '',
                    $row['programme_name'] ?? '',
                    $row['faculty_name'] ?? '',
                    $row['company_name'] ?? '',
                    $row['job_title'] ?? '',
                    $row['employment_status'] ?? '',
                    $row['industry'] ?? '',
                    $row['employment_type'] ?? '',
                    $row['is_current_job'] ?? 'No',
                    $row['start_date'] ?? '',
                ]);
            }
        }
    } elseif ($type === 'graduates') {
        fputcsv($output, [
            'Graduate ID',
            'Full Name',
            'Email',
            'Gender',
            'Phone Number',
            'County',
            'Sub County',
            'Programme',
            'Faculty',
            'Admission Number',
            'Registration Number',
            'Year of Graduation',
            'Degree Awarded',
        ]);

        $query = 'SELECT 
                    gp.graduate_profile_id,
                    CONCAT(u.first_name, \' \', u.last_name) AS full_name,
                    u.email,
                    pp.gender,
                    pp.phone_number,
                    gp.county,
                    gp.sub_county,
                    p.name AS programme_name,
                    f.name AS faculty_name,
                    gq.admission_number,
                    gq.registration_number,
                    gq.year_of_graduation,
                    gq.degree_awarded
                  FROM graduate_profiles gp
                  INNER JOIN users u ON u.user_id = gp.user_id
                  LEFT JOIN person_profiles pp ON pp.user_id = u.user_id
                  LEFT JOIN graduate_qualifications gq ON gq.graduate_profile_id = gp.graduate_profile_id
                  LEFT JOIN programmes p ON p.programme_id = gq.programme_id
                  LEFT JOIN departments d ON d.department_id = p.department_id
                  LEFT JOIN faculties f ON f.faculty_id = d.faculty_id
                  ORDER BY gp.created_at DESC';

        $result = $connection->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['graduate_profile_id'] ?? '',
                    $row['full_name'] ?? '',
                    $row['email'] ?? '',
                    $row['gender'] ?? '',
                    $row['phone_number'] ?? '',
                    $row['county'] ?? '',
                    $row['sub_county'] ?? '',
                    $row['programme_name'] ?? '',
                    $row['faculty_name'] ?? '',
                    $row['admission_number'] ?? '',
                    $row['registration_number'] ?? '',
                    $row['year_of_graduation'] ?? '',
                    $row['degree_awarded'] ?? '',
                ]);
            }
        }
    } elseif ($type === 'surveys') {
        fputcsv($output, [
            'Survey Title',
            'Graduate Name',
            'Email',
            'Sent At',
            'Response Status',
            'Completed At',
        ]);

        $query = 'SELECT 
                    s.name AS survey_title,
                    CONCAT(u.first_name, \' \', u.last_name) AS graduate_name,
                    u.email,
                    si.sent_at,
                    srs.name AS response_status,
                    si.completed_at
                  FROM survey_invitations si
                  INNER JOIN surveys s ON s.survey_id = si.survey_id
                  INNER JOIN graduate_profiles gp ON gp.graduate_profile_id = si.graduate_profile_id
                  INNER JOIN users u ON u.user_id = gp.user_id
                  LEFT JOIN survey_response_statuses srs ON srs.survey_response_status_id = si.survey_response_status_id
                  ORDER BY si.created_at DESC';

        $result = $connection->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['survey_title'] ?? '',
                    $row['graduate_name'] ?? '',
                    $row['email'] ?? '',
                    $row['sent_at'] ?? '',
                    $row['response_status'] ?? '',
                    $row['completed_at'] ?? '',
                ]);
            }
        }
    } elseif ($type === 'events') {
        fputcsv($output, [
            'Event Title',
            'Event Type',
            'Venue',
            'Start Datetime',
            'Graduate Name',
            'Email',
            'Registration Status',
            'Registered At',
            'Attended',
            'Attended At',
        ]);

        $query = 'SELECT 
                    e.title AS event_title,
                    e.event_type,
                    e.venue,
                    e.start_datetime,
                    CONCAT(u.first_name, \' \', u.last_name) AS graduate_name,
                    u.email,
                    er.registration_status,
                    er.registered_at,
                    CASE WHEN er.attended = 1 THEN \'Yes\' ELSE \'No\' END AS attended,
                    er.attended_at
                  FROM event_registrations er
                  INNER JOIN events e ON e.event_id = er.event_id
                  INNER JOIN graduate_profiles gp ON gp.graduate_profile_id = er.graduate_profile_id
                  INNER JOIN users u ON u.user_id = gp.user_id
                  ORDER BY er.registered_at DESC';

        $result = $connection->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['event_title'] ?? '',
                    $row['event_type'] ?? '',
                    $row['venue'] ?? '',
                    $row['start_datetime'] ?? '',
                    $row['graduate_name'] ?? '',
                    $row['email'] ?? '',
                    $row['registration_status'] ?? '',
                    $row['registered_at'] ?? '',
                    $row['attended'] ?? 'No',
                    $row['attended_at'] ?? '',
                ]);
            }
        }
    }

    fclose($output);
    exit;
}
