<?php

declare(strict_types=1);

/**
 * Safely parse date strings into MySQL datetime standard format.
 */
function parse_event_datetime(?string $input): ?string
{
    if ($input === null || trim($input) === '') {
        return null;
    }
    try {
        $dt = new DateTimeImmutable(trim($input));
        return $dt->format('Y-m-d H:i:s.u');
    } catch (Throwable) {
        return null;
    }
}

/**
 * Helper to fetch a single event by ID for management responses.
 *
 * @return array<string, mixed>|null
 */
function fetch_management_event_by_id(mysqli $connection, int $eventId): ?array
{
    $stmt = $connection->prepare(
        'SELECT 
            e.event_id,
            e.created_by,
            e.title,
            e.description,
            e.event_type,
            e.venue,
            e.start_datetime,
            e.end_datetime,
            e.registration_deadline,
            e.max_attendees,
            e.is_active,
            e.created_at,
            e.updated_at,
            COUNT(er.event_registration_id) AS registrations_count
         FROM events AS e
         LEFT JOIN event_registrations AS er ON er.event_id = e.event_id
         WHERE e.event_id = ?
         GROUP BY e.event_id, e.created_by, e.title, e.description, e.event_type, e.venue, e.start_datetime, e.end_datetime, e.registration_deadline, e.max_attendees, e.is_active, e.created_at, e.updated_at
         LIMIT 1'
    );
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        return null;
    }

    $maxAttendees = $row['max_attendees'] !== null ? (int) $row['max_attendees'] : null;
    $registrationsCount = (int) $row['registrations_count'];

    return [
        'event_id' => (int) $row['event_id'],
        'created_by' => (int) $row['created_by'],
        'title' => (string) $row['title'],
        'description' => $row['description'] !== null ? (string) $row['description'] : null,
        'venue' => $row['venue'] !== null ? (string) $row['venue'] : null,
        'location' => $row['venue'] !== null ? (string) $row['venue'] : null,
        'event_type' => $row['event_type'] !== null ? (string) $row['event_type'] : null,
        'start_datetime' => (string) $row['start_datetime'],
        'end_datetime' => $row['end_datetime'] !== null ? (string) $row['end_datetime'] : null,
        'registration_deadline' => $row['registration_deadline'] !== null ? (string) $row['registration_deadline'] : null,
        'max_attendees' => $maxAttendees,
        'is_active' => (int) $row['is_active'],
        'status' => ((int) $row['is_active']) === 1 ? 'published' : 'draft',
        'registrations_count' => $registrationsCount,
        'registered_count' => $registrationsCount,
        'created_at' => (string) $row['created_at'],
        'updated_at' => (string) $row['updated_at'],
    ];
}

/**
 * Helper to fetch a single announcement by ID for management responses.
 *
 * @return array<string, mixed>|null
 */
function fetch_management_announcement_by_id(mysqli $connection, int $announcementId): ?array
{
    $stmt = $connection->prepare(
        'SELECT 
            a.announcement_id,
            a.created_by,
            a.title,
            a.message,
            a.announcement_type,
            a.published_at,
            a.expires_at,
            a.is_active,
            a.created_at,
            a.updated_at,
            COUNT(ar.announcement_recipient_id) AS recipients_count
         FROM announcements AS a
         LEFT JOIN announcement_recipients AS ar ON ar.announcement_id = a.announcement_id
         WHERE a.announcement_id = ?
         GROUP BY a.announcement_id, a.created_by, a.title, a.message, a.announcement_type, a.published_at, a.expires_at, a.is_active, a.created_at, a.updated_at
         LIMIT 1'
    );
    $stmt->bind_param('i', $announcementId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        return null;
    }

    return [
        'announcement_id' => (int) $row['announcement_id'],
        'created_by' => (int) $row['created_by'],
        'title' => (string) $row['title'],
        'content' => (string) $row['message'],
        'message' => (string) $row['message'],
        'target_role' => $row['announcement_type'] !== null ? (string) $row['announcement_type'] : 'graduate',
        'announcement_type' => $row['announcement_type'] !== null ? (string) $row['announcement_type'] : 'graduate',
        'published_at' => $row['published_at'] !== null ? (string) $row['published_at'] : null,
        'expires_at' => $row['expires_at'] !== null ? (string) $row['expires_at'] : null,
        'is_active' => (int) $row['is_active'],
        'status' => $row['published_at'] !== null ? 'published' : 'draft',
        'recipients_count' => (int) $row['recipients_count'],
        'created_at' => (string) $row['created_at'],
        'updated_at' => (string) $row['updated_at'],
    ];
}

// 1. List Events (GET /management/events)
function list_management_events(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $stmt = $connection->prepare(
        'SELECT 
            e.event_id,
            e.created_by,
            e.title,
            e.description,
            e.event_type,
            e.venue,
            e.start_datetime,
            e.end_datetime,
            e.registration_deadline,
            e.max_attendees,
            e.is_active,
            e.created_at,
            e.updated_at,
            COUNT(er.event_registration_id) AS registrations_count
         FROM events AS e
         LEFT JOIN event_registrations AS er ON er.event_id = e.event_id
         GROUP BY e.event_id, e.created_by, e.title, e.description, e.event_type, e.venue, e.start_datetime, e.end_datetime, e.registration_deadline, e.max_attendees, e.is_active, e.created_at, e.updated_at
         ORDER BY e.created_at DESC, e.event_id DESC'
    );
    $stmt->execute();
    $rawEvents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $events = [];
    foreach ($rawEvents as $row) {
        $maxAttendees = $row['max_attendees'] !== null ? (int) $row['max_attendees'] : null;
        $registrationsCount = (int) $row['registrations_count'];

        $events[] = [
            'event_id' => (int) $row['event_id'],
            'created_by' => (int) $row['created_by'],
            'title' => (string) $row['title'],
            'description' => $row['description'] !== null ? (string) $row['description'] : null,
            'venue' => $row['venue'] !== null ? (string) $row['venue'] : null,
            'location' => $row['venue'] !== null ? (string) $row['venue'] : null,
            'event_type' => $row['event_type'] !== null ? (string) $row['event_type'] : null,
            'start_datetime' => (string) $row['start_datetime'],
            'end_datetime' => $row['end_datetime'] !== null ? (string) $row['end_datetime'] : null,
            'registration_deadline' => $row['registration_deadline'] !== null ? (string) $row['registration_deadline'] : null,
            'max_attendees' => $maxAttendees,
            'is_active' => (int) $row['is_active'],
            'status' => ((int) $row['is_active']) === 1 ? 'published' : 'draft',
            'registrations_count' => $registrationsCount,
            'registered_count' => $registrationsCount,
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    json_success('Management events retrieved successfully.', $events);
}

// 2. Create Event (POST /management/events)
function create_management_event(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $input = request_json_body();
    $title = required_string($input, 'title', 250);
    $description = optional_string($input, 'description', 65535);
    $venue = optional_string($input, 'venue', 255);
    if ($venue === null) {
        $venue = optional_string($input, 'location', 255);
    }
    $eventType = optional_string($input, 'event_type', 50);

    $rawStart = optional_string($input, 'start_datetime', 50) ?? optional_string($input, 'start_date', 50);
    if ($rawStart === null) {
        json_error('Validation failed.', ['start_datetime' => 'Start date and time is required.'], 422);
    }
    $startDatetime = parse_event_datetime($rawStart);
    if ($startDatetime === null) {
        json_error('Validation failed.', ['start_datetime' => 'Start datetime must be a valid date/time format.'], 422);
    }

    $rawEnd = optional_string($input, 'end_datetime', 50) ?? optional_string($input, 'end_date', 50);
    $endDatetime = parse_event_datetime($rawEnd);
    if ($rawEnd !== null && $endDatetime === null) {
        json_error('Validation failed.', ['end_datetime' => 'End datetime must be a valid date/time format.'], 422);
    }

    if ($endDatetime !== null && strtotime($endDatetime) < strtotime($startDatetime)) {
        json_error('Validation failed.', ['end_datetime' => 'End datetime must be on or after start datetime.'], 422);
    }

    $rawDeadline = optional_string($input, 'registration_deadline', 50);
    $registrationDeadline = parse_event_datetime($rawDeadline);

    $maxAttendees = isset($input['max_attendees']) ? (int) $input['max_attendees'] : (isset($input['capacity']) ? (int) $input['capacity'] : null);
    $isActive = isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 0;
    $createdBy = (int) $authenticated['user_id'];

    $connection = database_connection();
    management_profile_id($connection, $createdBy);

    $connection->begin_transaction();
    try {
        $stmt = $connection->prepare(
            'INSERT INTO events (created_by, title, description, event_type, venue, start_datetime, end_datetime, registration_deadline, max_attendees, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssssssii', $createdBy, $title, $description, $eventType, $venue, $startDatetime, $endDatetime, $registrationDeadline, $maxAttendees, $isActive);
        $stmt->execute();
        $eventId = (int) $connection->insert_id;
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $created = fetch_management_event_by_id($connection, $eventId);
    json_success('Event created successfully.', $created, 201);
}

// 3. Update Event (PUT /management/events/{id})
function update_management_event(int $eventId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $input = request_json_body();
    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $existing = fetch_management_event_by_id($connection, $eventId);
    if ($existing === null) {
        json_error('Event was not found.', [], 404);
    }

    $title = required_string($input, 'title', 250);
    $description = optional_string($input, 'description', 65535);
    $venue = optional_string($input, 'venue', 255) ?? optional_string($input, 'location', 255);
    $eventType = optional_string($input, 'event_type', 50);

    $rawStart = optional_string($input, 'start_datetime', 50) ?? optional_string($input, 'start_date', 50);
    $startDatetime = $rawStart !== null ? parse_event_datetime($rawStart) : (string) $existing['start_datetime'];
    if ($startDatetime === null) {
        json_error('Validation failed.', ['start_datetime' => 'Start datetime must be a valid date/time format.'], 422);
    }

    $rawEnd = optional_string($input, 'end_datetime', 50) ?? optional_string($input, 'end_date', 50);
    $endDatetime = $rawEnd !== null ? parse_event_datetime($rawEnd) : $existing['end_datetime'];

    if ($endDatetime !== null && strtotime((string)$endDatetime) < strtotime((string)$startDatetime)) {
        json_error('Validation failed.', ['end_datetime' => 'End datetime must be on or after start datetime.'], 422);
    }

    $rawDeadline = optional_string($input, 'registration_deadline', 50);
    $registrationDeadline = $rawDeadline !== null ? parse_event_datetime($rawDeadline) : $existing['registration_deadline'];

    $maxAttendees = isset($input['max_attendees']) ? (int) $input['max_attendees'] : (isset($input['capacity']) ? (int) $input['capacity'] : $existing['max_attendees']);
    $isActive = isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : (int) $existing['is_active'];

    $connection->begin_transaction();
    try {
        $stmt = $connection->prepare(
            'UPDATE events 
             SET title = ?, description = ?, event_type = ?, venue = ?, start_datetime = ?, end_datetime = ?, registration_deadline = ?, max_attendees = ?, is_active = ?, updated_at = NOW(6)
             WHERE event_id = ?'
        );
        $stmt->bind_param('sssssssiii', $title, $description, $eventType, $venue, $startDatetime, $endDatetime, $registrationDeadline, $maxAttendees, $isActive, $eventId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $updated = fetch_management_event_by_id($connection, $eventId);
    json_success('Event updated successfully.', $updated);
}

// 4. Delete Event (DELETE /management/events/{id})
function delete_management_event(int $eventId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $existing = fetch_management_event_by_id($connection, $eventId);
    if ($existing === null) {
        json_error('Event was not found.', [], 404);
    }

    // Safety Rule: Check if event registrations exist
    $regStmt = $connection->prepare('SELECT COUNT(*) AS total FROM event_registrations WHERE event_id = ?');
    $regStmt->bind_param('i', $eventId);
    $regStmt->execute();
    $regRow = $regStmt->get_result()->fetch_assoc();
    $regStmt->close();

    $regCount = is_array($regRow) ? (int) $regRow['total'] : 0;
    if ($regCount > 0) {
        json_error('Event cannot be deleted because registrations already exist.', [], 422);
    }

    $connection->begin_transaction();
    try {
        $stmt = $connection->prepare('DELETE FROM events WHERE event_id = ?');
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Event deleted successfully.');
}

// 5. Publish Event (PUT /management/events/{id}/publish)
function publish_management_event(int $eventId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $existing = fetch_management_event_by_id($connection, $eventId);
    if ($existing === null) {
        json_error('Event was not found.', [], 404);
    }

    $connection->begin_transaction();
    try {
        $stmt = $connection->prepare('UPDATE events SET is_active = 1, updated_at = NOW(6) WHERE event_id = ?');
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $published = fetch_management_event_by_id($connection, $eventId);
    json_success('Event published successfully.', $published);
}

// 6. View Event Attendees (GET /management/events/{id}/attendees)
function get_management_event_attendees(int $eventId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $existing = fetch_management_event_by_id($connection, $eventId);
    if ($existing === null) {
        json_error('Event was not found.', [], 404);
    }

    $stmt = $connection->prepare(
        'SELECT 
            er.event_registration_id,
            er.event_id,
            er.graduate_profile_id,
            er.registration_status,
            er.registered_at,
            er.attended,
            er.attended_at,
            u.first_name,
            u.last_name,
            CONCAT(u.first_name, \' \', u.last_name) AS graduate_name,
            u.email
         FROM event_registrations AS er
         INNER JOIN graduate_profiles AS gp ON gp.graduate_profile_id = er.graduate_profile_id
         INNER JOIN users AS u ON u.user_id = gp.user_id
         WHERE er.event_id = ?
         ORDER BY er.registered_at DESC, er.event_registration_id DESC'
    );
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $rawAttendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $attendees = [];
    foreach ($rawAttendees as $row) {
        $attendees[] = [
            'event_registration_id' => (int) $row['event_registration_id'],
            'event_id' => (int) $row['event_id'],
            'graduate_profile_id' => (int) $row['graduate_profile_id'],
            'graduate_name' => (string) $row['graduate_name'],
            'first_name' => (string) $row['first_name'],
            'last_name' => (string) $row['last_name'],
            'email' => (string) $row['email'],
            'registration_status' => (string) $row['registration_status'],
            'registered_at' => (string) $row['registered_at'],
            'attended' => (int) $row['attended'],
            'attended_at' => $row['attended_at'] !== null ? (string) $row['attended_at'] : null,
        ];
    }

    json_success('Event attendees retrieved successfully.', $attendees);
}

// 7. List Announcements (GET /management/announcements)
function list_management_announcements(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $stmt = $connection->prepare(
        'SELECT 
            a.announcement_id,
            a.created_by,
            a.title,
            a.message,
            a.announcement_type,
            a.published_at,
            a.expires_at,
            a.is_active,
            a.created_at,
            a.updated_at,
            COUNT(ar.announcement_recipient_id) AS recipients_count
         FROM announcements AS a
         LEFT JOIN announcement_recipients AS ar ON ar.announcement_id = a.announcement_id
         GROUP BY a.announcement_id, a.created_by, a.title, a.message, a.announcement_type, a.published_at, a.expires_at, a.is_active, a.created_at, a.updated_at
         ORDER BY a.created_at DESC, a.announcement_id DESC'
    );
    $stmt->execute();
    $rawList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $announcements = [];
    foreach ($rawList as $row) {
        $announcements[] = [
            'announcement_id' => (int) $row['announcement_id'],
            'created_by' => (int) $row['created_by'],
            'title' => (string) $row['title'],
            'content' => (string) $row['message'],
            'message' => (string) $row['message'],
            'target_role' => $row['announcement_type'] !== null ? (string) $row['announcement_type'] : 'graduate',
            'announcement_type' => $row['announcement_type'] !== null ? (string) $row['announcement_type'] : 'graduate',
            'published_at' => $row['published_at'] !== null ? (string) $row['published_at'] : null,
            'expires_at' => $row['expires_at'] !== null ? (string) $row['expires_at'] : null,
            'is_active' => (int) $row['is_active'],
            'status' => $row['published_at'] !== null ? 'published' : 'draft',
            'recipients_count' => (int) $row['recipients_count'],
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    json_success('Management announcements retrieved successfully.', $announcements);
}

function list_graduate_announcements(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $graduateProfileId = get_graduate_profile_id($connection, (int) $authenticated['user_id']);
    $targetRole = 'graduate';

    $stmt = $connection->prepare(
        'SELECT 
            a.announcement_id,
            a.created_by,
            a.title,
            a.message,
            a.announcement_type,
            a.published_at,
            a.expires_at,
            a.is_active,
            a.created_at,
            a.updated_at,
            COALESCE(ar.is_read, 0) AS is_read,
            ar.read_at
         FROM announcements AS a
         LEFT JOIN announcement_recipients AS ar
           ON ar.announcement_id = a.announcement_id
          AND ar.graduate_profile_id = ?
         WHERE a.is_active = 1
           AND a.published_at IS NOT NULL
           AND (a.announcement_type = ? OR a.announcement_type IS NULL)
           AND (a.expires_at IS NULL OR a.expires_at > NOW(6))
         ORDER BY a.published_at DESC, a.announcement_id DESC'
    );
    $stmt->bind_param('is', $graduateProfileId, $targetRole);
    $stmt->execute();
    $rawList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $announcements = [];
    foreach ($rawList as $row) {
        $announcements[] = [
            'announcement_id' => (int) $row['announcement_id'],
            'created_by' => (int) $row['created_by'],
            'title' => (string) $row['title'],
            'content' => (string) $row['message'],
            'message' => (string) $row['message'],
            'target_role' => $row['announcement_type'] !== null ? (string) $row['announcement_type'] : 'graduate',
            'announcement_type' => $row['announcement_type'] !== null ? (string) $row['announcement_type'] : 'graduate',
            'published_at' => $row['published_at'] !== null ? (string) $row['published_at'] : null,
            'expires_at' => $row['expires_at'] !== null ? (string) $row['expires_at'] : null,
            'is_active' => (int) $row['is_active'],
            'status' => 'published',
            'is_new' => ((int) $row['is_read']) === 0,
            'viewed_at' => $row['read_at'] !== null ? (string) $row['read_at'] : null,
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    json_success('Graduate announcements retrieved successfully.', $announcements);
}

function mark_graduate_announcement_viewed(int $announcementId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $graduateProfileId = get_graduate_profile_id($connection, (int) $authenticated['user_id']);
    $targetRole = 'graduate';

    // The availability check prevents graduates from creating view records for
    // inactive, expired, or announcements addressed to another role.
    $availableStmt = $connection->prepare(
        'SELECT announcement_id
         FROM announcements
         WHERE announcement_id = ?
           AND is_active = 1
           AND published_at IS NOT NULL
           AND (announcement_type = ? OR announcement_type IS NULL)
           AND (expires_at IS NULL OR expires_at > NOW(6))
         LIMIT 1'
    );
    $availableStmt->bind_param('is', $announcementId, $targetRole);
    $availableStmt->execute();
    $available = $availableStmt->get_result()->fetch_assoc();
    $availableStmt->close();

    if (!is_array($available)) {
        json_error('Announcement was not found.', [], 404);
    }

    try {
        $connection->begin_transaction();

        // The unique recipient key makes repeated opens idempotent: the same
        // graduate can only have one per-announcement view state.
        $viewStmt = $connection->prepare(
            'INSERT INTO announcement_recipients (announcement_id, graduate_profile_id, is_read, read_at)
             VALUES (?, ?, 1, NOW(6))
             ON DUPLICATE KEY UPDATE is_read = 1, read_at = COALESCE(read_at, NOW(6))'
        );
        $viewStmt->bind_param('ii', $announcementId, $graduateProfileId);
        $viewStmt->execute();
        $viewStmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $viewStmt = $connection->prepare(
        'SELECT read_at FROM announcement_recipients WHERE announcement_id = ? AND graduate_profile_id = ? LIMIT 1'
    );
    $viewStmt->bind_param('ii', $announcementId, $graduateProfileId);
    $viewStmt->execute();
    $view = $viewStmt->get_result()->fetch_assoc();
    $viewStmt->close();

    json_success('Announcement marked as viewed.', [
        'announcement_id' => $announcementId,
        'is_new' => false,
        'viewed_at' => is_array($view) && $view['read_at'] !== null ? (string) $view['read_at'] : null,
    ]);
}

// 8. Create Announcement (POST /management/announcements)
function create_management_announcement(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $input = request_json_body();
    $title = required_string($input, 'title', 250);

    $content = null;
    if (isset($input['content']) && is_string($input['content'])) {
        $content = trim($input['content']);
    } elseif (isset($input['message']) && is_string($input['message'])) {
        $content = trim($input['message']);
    }
    if ($content === null || $content === '') {
        json_error('Validation failed.', ['content' => 'Content is required.'], 422);
    }

    $targetRole = optional_string($input, 'target_role', 50) ?? optional_string($input, 'announcement_type', 50) ?? 'graduate';
    $createdBy = (int) $authenticated['user_id'];

    $connection = database_connection();
    management_profile_id($connection, $createdBy);

    $connection->begin_transaction();
    try {
        $stmt = $connection->prepare(
            'INSERT INTO announcements (created_by, title, message, announcement_type, is_active)
             VALUES (?, ?, ?, ?, 0)'
        );
        $stmt->bind_param('isss', $createdBy, $title, $content, $targetRole);
        $stmt->execute();
        $announcementId = (int) $connection->insert_id;
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $created = fetch_management_announcement_by_id($connection, $announcementId);
    json_success('Announcement created successfully.', $created, 201);
}

// 9. Publish Announcement (PUT /management/announcements/{id}/publish)
function publish_management_announcement(int $announcementId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $existing = fetch_management_announcement_by_id($connection, $announcementId);
    if ($existing === null) {
        json_error('Announcement was not found.', [], 404);
    }

    // Publishing is an event. Treat a repeated publish request as idempotent so
    // it cannot create duplicate in-app notifications or announcement emails.
    if ($existing['published_at'] !== null) {
        json_success('Announcement is already published.', $existing);
    }

    $title = (string) $existing['title'];
    $content = (string) $existing['content'];
    $emailRecipients = [];

    $connection->begin_transaction();
    try {
        // Mark as published and active
        $stmt = $connection->prepare('UPDATE announcements SET published_at = NOW(6), is_active = 1, updated_at = NOW(6) WHERE announcement_id = ?');
        $stmt->bind_param('i', $announcementId);
        $stmt->execute();
        $stmt->close();

        // Populate announcement_recipients for targeted graduates if not already populated
        $recStmt = $connection->prepare(
            'INSERT IGNORE INTO announcement_recipients (announcement_id, graduate_profile_id)
             SELECT ?, gp.graduate_profile_id
             FROM graduate_profiles AS gp'
        );
        $recStmt->bind_param('i', $announcementId);
        $recStmt->execute();
        $recStmt->close();

        // Create in-app notifications first. Email is deliberately dispatched
        // after commit, so a provider failure cannot roll back publication.
        $graduateStmt = $connection->prepare('SELECT user_id FROM graduate_profiles');
        $graduateStmt->execute();
        $graduates = $graduateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $graduateStmt->close();
        foreach ($graduates as $graduate) {
            $graduateUserId = (int) $graduate['user_id'];
            $notificationId = create_mugts_in_app_notification($connection, $graduateUserId, $title, $content, 'announcement');
            $emailRecipients[] = ['user_id' => $graduateUserId, 'notification_id' => $notificationId];
        }

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    // The notification and email channels remain independent. This publication
    // event is the only place announcement emails are requested; list endpoints
    // never trigger delivery.
    if (($existing['target_role'] ?? 'graduate') === 'graduate') {
        foreach ($emailRecipients as $recipient) {
            send_mugts_notification_email(
                (int) $recipient['user_id'],
                $title,
                $title,
                $content,
                '/announcements',
                'email_announcements',
                (int) $recipient['notification_id'],
            );
        }
    }

    $published = fetch_management_announcement_by_id($connection, $announcementId);
    json_success('Announcement published successfully.', $published);
}

// 10. Delete Announcement (DELETE /management/announcements/{id})
function delete_management_announcement(int $announcementId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['management', 'administrator']);

    $connection = database_connection();
    management_profile_id($connection, (int) $authenticated['user_id']);

    $existing = fetch_management_announcement_by_id($connection, $announcementId);
    if ($existing === null) {
        json_error('Announcement was not found.', [], 404);
    }

    // Safety Rule: Prevent deleting published announcements
    if ($existing['published_at'] !== null || ((int) $existing['is_active']) === 1) {
        json_error('Published announcements cannot be deleted.', [], 422);
    }

    $connection->begin_transaction();
    try {
        $delRec = $connection->prepare('DELETE FROM announcement_recipients WHERE announcement_id = ?');
        $delRec->bind_param('i', $announcementId);
        $delRec->execute();
        $delRec->close();

        $stmt = $connection->prepare('DELETE FROM announcements WHERE announcement_id = ?');
        $stmt->bind_param('i', $announcementId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Announcement deleted successfully.');
}
