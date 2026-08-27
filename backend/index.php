<?php

declare(strict_types=1);

date_default_timezone_set('Africa/Nairobi');

require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/utilities.php';
require_once __DIR__ . '/helpers/validation.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/helpers/mail.php';
require_once __DIR__ . '/helpers/notification_service.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/controllers/auth.php';
require_once __DIR__ . '/controllers/profile.php';
require_once __DIR__ . '/controllers/dashboard.php';
require_once __DIR__ . '/controllers/qualifications.php';
require_once __DIR__ . '/controllers/notifications.php';
require_once __DIR__ . '/controllers/programmes.php';
require_once __DIR__ . '/controllers/employment.php';
require_once __DIR__ . '/controllers/entrepreneurship.php';
require_once __DIR__ . '/controllers/recommendations.php';
require_once __DIR__ . '/controllers/management_dashboard.php';
require_once __DIR__ . '/controllers/management_recommendations.php';
require_once __DIR__ . '/controllers/management_surveys.php';
require_once __DIR__ . '/controllers/management_events.php';
require_once __DIR__ . '/controllers/management_reports.php';
require_once __DIR__ . '/controllers/administrator_dashboard.php';
require_once __DIR__ . '/controllers/administrator_users.php';
require_once __DIR__ . '/controllers/administrator_roles.php';
require_once __DIR__ . '/controllers/administrator_academics.php';
require_once __DIR__ . '/controllers/administrator_settings.php';
require_once __DIR__ . '/controllers/events.php';

require_once __DIR__ . '/controllers/surveys.php';
require_once __DIR__ . '/routes/api.php';

set_exception_handler(static function (Throwable $exception): never {
    error_log($exception->getMessage());
    json_error('An unexpected server error occurred.', [], 500);
});

apply_cors_headers();

if (request_method() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

dispatch_api_route(request_method(), request_path());



