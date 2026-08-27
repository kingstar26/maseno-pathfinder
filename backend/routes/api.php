<?php

declare(strict_types=1);

function dispatch_api_route(string $method, string $path): never
{
    if ($method === 'POST' && $path === '/auth/register') register_graduate();
    if ($method === 'POST' && $path === '/auth/login') login_user();
    if ($method === 'POST' && $path === '/auth/change-password') change_user_password();
    if ($method === 'GET' && $path === '/programmes') list_programmes();
    if ($method === 'GET' && $path === '/academics/meta') list_academic_metadata();
    if ($method === 'GET' && $path === '/employment-statuses') list_employment_statuses();
    if ($method === 'GET' && $path === '/auth/me') get_authenticated_user();
    if ($method === 'GET' && $path === '/user/preferences') get_user_preferences();
    if ($method === 'PUT' && $path === '/user/preferences') update_user_preferences();
    if ($method === 'GET' && $path === '/graduate/dashboard') get_graduate_dashboard();
    if ($method === 'GET' && $path === '/graduate/profile') get_graduate_profile();
    if ($method === 'PUT' && $path === '/graduate/profile') update_graduate_profile();
    if ($method === 'POST' && ($path === '/graduate/profile/avatar' || $path === '/user/avatar' || $path === '/profile/avatar')) upload_profile_avatar();
    if ($method === 'POST' && $path === '/graduate/profile/cv') upload_cv();
    if ($method === 'DELETE' && $path === '/graduate/profile/cv') delete_cv();
    if ($method === 'GET' && $path === '/graduate/qualifications') list_graduate_qualifications();
    if ($method === 'POST' && $path === '/graduate/qualifications') create_graduate_qualification();
    if (preg_match('#^/graduate/qualifications/(\\d+)$#', $path, $matches) === 1) {
        $qualificationId = (int) $matches[1];
        if ($qualificationId > 0 && $method === 'PUT') update_graduate_qualification($qualificationId);
        if ($qualificationId > 0 && $method === 'DELETE') delete_graduate_qualification($qualificationId);
    }
    if ($method === 'GET' && $path === '/graduate/notifications') list_graduate_notifications();
    if ($method === 'GET' && $path === '/graduate/notifications/unread-count') get_graduate_notifications_unread_count();
    if ($method === 'PUT' && $path === '/graduate/notifications/read-all') mark_all_graduate_notifications_read();
    if (preg_match('#^/graduate/notifications/(\\d+)/read$#', $path, $matches) === 1) {
        $notificationId = (int) $matches[1];
        if ($notificationId > 0 && $method === 'PUT') mark_graduate_notification_read($notificationId);
    }
    if (preg_match('#^/graduate/notifications/(\d+)$#', $path, $matches) === 1) {
        $notificationId = (int) $matches[1];
        if ($notificationId > 0 && $method === 'DELETE') delete_graduate_notification($notificationId);
    }
    if ($method === 'DELETE' && $path === '/graduate/notifications') delete_graduate_notifications_bulk();
    if ($method === 'GET' && $path === '/graduate/announcements') list_graduate_announcements();
    if (preg_match('#^/graduate/announcements/(\d+)/view$#', $path, $matches) === 1) {
        $announcementId = (int) $matches[1];
        if ($announcementId > 0 && $method === 'PUT') mark_graduate_announcement_viewed($announcementId);
    }
    if ($method === 'GET' && $path === '/graduate/employment') list_graduate_employment();
    if ($method === 'POST' && $path === '/graduate/employment') create_graduate_employment();
    if ($method === 'GET' && $path === '/graduate/entrepreneurship') list_graduate_entrepreneurship();
    if ($method === 'POST' && $path === '/graduate/entrepreneurship') create_graduate_entrepreneurship();
    if ($method === 'GET' && $path === '/graduate/recommendations') list_graduate_recommendations();
    if ($method === 'POST' && $path === '/graduate/recommendations') create_recommendation_request();
    if (preg_match('#^/graduate/recommendations/(\\d+)/download$#', $path, $matches) === 1) {
        $requestId = (int) $matches[1];
        if ($requestId > 0 && $method === 'GET') download_recommendation_letter($requestId);
    }
    if (preg_match('#^/graduate/recommendations/(\\d+)$#', $path, $matches) === 1) {
        $requestId = (int) $matches[1];
        if ($requestId > 0 && $method === 'GET') get_recommendation_details($requestId);
        if ($requestId > 0 && $method === 'PUT') update_graduate_recommendation($requestId);
        if ($requestId > 0 && $method === 'DELETE') delete_graduate_recommendation($requestId);
    }
    if (preg_match('#^/graduate/entrepreneurship/(\\d+)$#', $path, $matches) === 1) {
        $businessId = (int) $matches[1];
        if ($businessId > 0 && $method === 'PUT') update_graduate_entrepreneurship($businessId);
        if ($businessId > 0 && $method === 'DELETE') delete_graduate_entrepreneurship($businessId);
    }
    if (preg_match('#^/graduate/employment/(\\d+)$#', $path, $matches) === 1) {
        $recordId = (int) $matches[1];
        if ($recordId > 0 && $method === 'PUT') update_graduate_employment($recordId);
        if ($recordId > 0 && $method === 'DELETE') delete_graduate_employment($recordId);
    }
    if ($method === 'GET' && $path === '/events') list_upcoming_events();
    if ($method === 'GET' && $path === '/graduate/events') list_upcoming_events();
    if (preg_match('#^/graduate/events/(\\d+)$#', $path, $matches) === 1) {
        $eventId = (int) $matches[1];
        if ($eventId > 0 && $method === 'GET') get_event_details($eventId);
    }
    if (preg_match('#^/graduate/events/(\\d+)/register$#', $path, $matches) === 1) {
        $eventId = (int) $matches[1];
        if ($eventId > 0 && $method === 'POST') register_for_event($eventId);
        if ($eventId > 0 && $method === 'DELETE') cancel_event_registration($eventId);
    }
    if ($method === 'GET' && $path === '/surveys') list_available_surveys();
    if ($method === 'GET' && $path === '/graduate/surveys') list_available_surveys();
    if ($method === 'GET' && $path === '/graduate/surveys/completed') get_completed_surveys();
    if ($method === 'GET' && $path === '/graduate/surveys/responses') list_my_survey_responses();
    if (preg_match('#^/surveys/(\\d+)$#', $path, $matches) === 1) {
        $surveyId = (int) $matches[1];
        if ($surveyId > 0 && $method === 'GET') get_survey_details($surveyId);
    }
    if (preg_match('#^/graduate/surveys/(\\d+)$#', $path, $matches) === 1) {
        $surveyId = (int) $matches[1];
        if ($surveyId > 0 && $method === 'GET') get_survey_details($surveyId);
    }
    if (preg_match('#^/graduate/surveys/(\\d+)/responses$#', $path, $matches) === 1) {
        $surveyId = (int) $matches[1];
        if ($surveyId > 0 && $method === 'POST') submit_survey_response($surveyId);
        if ($surveyId > 0 && $method === 'GET') get_survey_response($surveyId);
    }
    if (preg_match('#^/graduate/surveys/(\\d+)/start$#', $path, $matches) === 1) {
        $surveyId = (int) $matches[1];
        if ($surveyId > 0 && $method === 'POST') start_survey_response($surveyId);
    }
    if (preg_match('#^/graduate/surveys/(\\d+)/submit$#', $path, $matches) === 1) {
        $responseId = (int) $matches[1];
        if ($responseId > 0 && $method === 'POST') submit_survey_response($responseId);
    }
    // Management Routes
    if ($method === 'GET' && $path === '/management/dashboard') get_management_dashboard();
    if ($method === 'GET' && $path === '/management/recommendations') list_management_recommendations();
    if (preg_match('#^/management/recommendations/(\\d+)/approve$#', $path, $matches) === 1) {
        $requestId = (int) $matches[1];
        if ($requestId > 0 && $method === 'PUT') approve_recommendation_request($requestId);
    }
    if (preg_match('#^/management/recommendations/(\\d+)/reject$#', $path, $matches) === 1) {
        $requestId = (int) $matches[1];
        if ($requestId > 0 && $method === 'PUT') reject_recommendation_request($requestId);
    }
    if (preg_match('#^/management/recommendations/(\\d+)/upload-letter$#', $path, $matches) === 1) {
        $requestId = (int) $matches[1];
        if ($requestId > 0 && $method === 'POST') upload_recommendation_letter($requestId);
    }
    if (preg_match('#^/management/recommendations/(\\d+)$#', $path, $matches) === 1) {
        $requestId = (int) $matches[1];
        if ($requestId > 0 && $method === 'GET') get_management_recommendation_details($requestId);
    }
    // Management Survey Routes
    if ($method === 'GET' && $path === '/management/surveys') list_management_surveys();
    if ($method === 'POST' && $path === '/management/surveys') create_management_survey();
    if (preg_match('#^/management/surveys/(\\d+)/publish$#', $path, $matches) === 1) {
        $surveyId = (int) $matches[1];
        if ($surveyId > 0 && $method === 'PUT') publish_management_survey($surveyId);
    }
    if (preg_match('#^/management/surveys/(\\d+)/sections$#', $path, $matches) === 1) {
        $surveyId = (int) $matches[1];
        if ($surveyId > 0 && $method === 'POST') create_management_survey_section($surveyId);
    }
    if (preg_match('#^/management/surveys/(\\d+)/questions$#', $path, $matches) === 1) {
        $surveyId = (int) $matches[1];
        if ($surveyId > 0 && $method === 'POST') create_management_survey_question($surveyId);
    }
    if (preg_match('#^/management/questions/(\\d+)/options$#', $path, $matches) === 1) {
        $questionId = (int) $matches[1];
        if ($questionId > 0 && $method === 'POST') create_management_question_option($questionId);
    }
    if (preg_match('#^/management/surveys/(\\d+)$#', $path, $matches) === 1) {
        $surveyId = (int) $matches[1];
        if ($surveyId > 0 && $method === 'GET') get_management_survey_details($surveyId);
        if ($surveyId > 0 && $method === 'PUT') update_management_survey($surveyId);
        if ($surveyId > 0 && $method === 'DELETE') delete_management_survey($surveyId);
    }
    // Management Event Routes
    if ($method === 'GET' && $path === '/management/events') list_management_events();
    if ($method === 'POST' && $path === '/management/events') create_management_event();
    if (preg_match('#^/management/events/(\\d+)/publish$#', $path, $matches) === 1) {
        $eventId = (int) $matches[1];
        if ($eventId > 0 && $method === 'PUT') publish_management_event($eventId);
    }
    if (preg_match('#^/management/events/(\\d+)/attendees$#', $path, $matches) === 1) {
        $eventId = (int) $matches[1];
        if ($eventId > 0 && $method === 'GET') get_management_event_attendees($eventId);
    }
    if (preg_match('#^/management/events/(\\d+)$#', $path, $matches) === 1) {
        $eventId = (int) $matches[1];
        if ($eventId > 0 && $method === 'PUT') update_management_event($eventId);
        if ($eventId > 0 && $method === 'DELETE') delete_management_event($eventId);
    }
    // Management Announcement Routes
    if ($method === 'GET' && $path === '/management/announcements') list_management_announcements();
    if ($method === 'POST' && $path === '/management/announcements') create_management_announcement();
    if (preg_match('#^/management/announcements/(\\d+)/publish$#', $path, $matches) === 1) {
        $announcementId = (int) $matches[1];
        if ($announcementId > 0 && $method === 'PUT') publish_management_announcement($announcementId);
    }
    if (preg_match('#^/management/announcements/(\\d+)$#', $path, $matches) === 1) {
        $announcementId = (int) $matches[1];
        if ($announcementId > 0 && $method === 'DELETE') delete_management_announcement($announcementId);
    }
    // Management Reports Routes
    if ($method === 'GET' && $path === '/management/reports/overview') get_management_reports_overview();
    if ($method === 'GET' && $path === '/management/reports/employment') get_management_reports_employment();
    if ($method === 'GET' && $path === '/management/reports/graduates') get_management_reports_graduates();
    if ($method === 'GET' && $path === '/management/reports/surveys') get_management_reports_surveys();
    if ($method === 'GET' && $path === '/management/reports/events') get_management_reports_events();
    if ($method === 'GET' && $path === '/management/reports/recommendations') get_management_reports_recommendations();
    if ($method === 'GET' && $path === '/management/reports/export') export_management_reports();

    // Administrator routes
    if ($method === 'GET' && $path === '/administrator/dashboard') get_administrator_dashboard();
    if ($method === 'GET' && $path === '/administrator/users') list_administrator_users();
    if (preg_match('#^/administrator/users/(\d+)$#', $path, $matches) === 1 && $method === 'GET') get_administrator_user((int) $matches[1]);
    if (preg_match('#^/administrator/users/(\d+)/status$#', $path, $matches) === 1 && $method === 'PUT') update_administrator_user_status((int) $matches[1]);
    if (preg_match('#^/administrator/users/(\d+)/role$#', $path, $matches) === 1 && $method === 'PUT') update_administrator_user_role((int) $matches[1]);
    if ($method === 'GET' && $path === '/administrator/roles') list_administrator_roles();
    if ($method === 'GET' && $path === '/administrator/statuses') list_administrator_statuses();
    if ($method === 'GET' && $path === '/administrator/faculties') list_administrator_faculties();
    if ($method === 'POST' && $path === '/administrator/faculties') create_administrator_faculty();
    if (preg_match('#^/administrator/faculties/(\d+)$#', $path, $matches) === 1) {
        if ($method === 'PUT') update_administrator_faculty((int) $matches[1]);
        if ($method === 'DELETE') delete_administrator_faculty((int) $matches[1]);
    }
    if ($method === 'GET' && $path === '/administrator/departments') list_administrator_departments();
    if ($method === 'POST' && $path === '/administrator/departments') create_administrator_department();
    if (preg_match('#^/administrator/departments/(\d+)$#', $path, $matches) === 1) {
        if ($method === 'PUT') update_administrator_department((int) $matches[1]);
        if ($method === 'DELETE') delete_administrator_department((int) $matches[1]);
    }
    if ($method === 'GET' && $path === '/administrator/programmes') list_administrator_programmes();
    if ($method === 'POST' && $path === '/administrator/programmes') create_administrator_programme();
    if (preg_match('#^/administrator/programmes/(\d+)$#', $path, $matches) === 1) {
        if ($method === 'PUT') update_administrator_programme((int) $matches[1]);
        if ($method === 'DELETE') delete_administrator_programme((int) $matches[1]);
    }
    if ($method === 'GET' && $path === '/administrator/settings') get_administrator_settings();
    if ($method === 'PUT' && $path === '/administrator/settings') update_administrator_settings();
    json_error('The requested API route was not found.', [], 404);
}
