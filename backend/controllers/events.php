<?php

declare(strict_types=1);

/** @return array<string, mixed> */
function graduate_events_profile(mysqli $connection, int $userId): array
{
    $statement = $connection->prepare('SELECT graduate_profile_id FROM graduate_profiles WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $profile = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($profile)) {
        json_error('Graduate profile was not found.', [], 404);
    }

    return $profile;
}

/** @return array<string, mixed>|null */
function graduate_event_registration(mysqli $connection, int $graduateProfileId, int $registrationId): ?array
{
    $statement = $connection->prepare(
        'SELECT event_registration_id, event_id, graduate_profile_id, registration_status, registered_at, attended, attended_at 
         FROM event_registrations 
         WHERE event_registration_id = ? AND graduate_profile_id = ? 
         LIMIT 1'
    );
    $statement->bind_param('ii', $registrationId, $graduateProfileId);
    $statement->execute();
    $record = $statement->get_result()->fetch_assoc();
    $statement->close();

    return is_array($record) ? $record : null;
}

function list_upcoming_events(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $profile = graduate_events_profile($connection, $authenticated['user_id']);
    $graduateProfileId = (int) $profile['graduate_profile_id'];

    $statement = $connection->prepare(
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
            COUNT(er.event_registration_id) AS registered_count,
            MAX(CASE WHEN er.graduate_profile_id = ? THEN 1 ELSE 0 END) AS is_registered
         FROM events AS e
         LEFT JOIN event_registrations AS er ON er.event_id = e.event_id
         WHERE e.is_active = 1 
           AND e.start_datetime >= NOW(6)
           AND (e.registration_deadline IS NULL OR e.registration_deadline >= NOW(6))
         GROUP BY e.event_id
         ORDER BY e.start_datetime ASC, e.event_id ASC'
    );
    $statement->bind_param('i', $graduateProfileId);
    $statement->execute();
    $rawEvents = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $events = [];
    foreach ($rawEvents as $row) {
        $maxAttendees = $row['max_attendees'] !== null ? (int) $row['max_attendees'] : null;
        $registeredCount = (int) $row['registered_count'];
        $availableSlots = $maxAttendees !== null ? max(0, $maxAttendees - $registeredCount) : null;
        $isRegistered = ((int) $row['is_registered']) === 1;

        $events[] = [
            'event_id' => (int) $row['event_id'],
            'title' => (string) $row['title'],
            'description' => $row['description'] !== null ? (string) $row['description'] : null,
            'location' => $row['venue'] !== null ? (string) $row['venue'] : null,
            'venue' => $row['venue'] !== null ? (string) $row['venue'] : null,
            'event_type' => $row['event_type'] !== null ? (string) $row['event_type'] : null,
            'start_date' => (string) $row['start_datetime'],
            'start_datetime' => (string) $row['start_datetime'],
            'end_date' => $row['end_datetime'] !== null ? (string) $row['end_datetime'] : null,
            'end_datetime' => $row['end_datetime'] !== null ? (string) $row['end_datetime'] : null,
            'registration_deadline' => $row['registration_deadline'] !== null ? (string) $row['registration_deadline'] : null,
            'capacity' => $maxAttendees,
            'max_attendees' => $maxAttendees,
            'registered_count' => $registeredCount,
            'available_slots' => $availableSlots,
            'is_registered' => $isRegistered,
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    json_success('Upcoming events retrieved successfully.', $events);
}

function get_event_details(int $eventId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $profile = graduate_events_profile($connection, $authenticated['user_id']);
    $graduateProfileId = (int) $profile['graduate_profile_id'];

    $statement = $connection->prepare(
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
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) AS registered_count,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND graduate_profile_id = ?) AS is_registered
         FROM events AS e
         WHERE e.event_id = ?
         LIMIT 1'
    );
    $statement->bind_param('ii', $graduateProfileId, $eventId);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($row)) {
        json_error('Event was not found.', [], 404);
    }

    $maxAttendees = $row['max_attendees'] !== null ? (int) $row['max_attendees'] : null;
    $registeredCount = (int) $row['registered_count'];
    $availableSlots = $maxAttendees !== null ? max(0, $maxAttendees - $registeredCount) : null;
    $isRegistered = ((int) $row['is_registered']) > 0;

    $eventData = [
        'event_id' => (int) $row['event_id'],
        'title' => (string) $row['title'],
        'description' => $row['description'] !== null ? (string) $row['description'] : null,
        'location' => $row['venue'] !== null ? (string) $row['venue'] : null,
        'venue' => $row['venue'] !== null ? (string) $row['venue'] : null,
        'event_type' => $row['event_type'] !== null ? (string) $row['event_type'] : null,
        'start_date' => (string) $row['start_datetime'],
        'start_datetime' => (string) $row['start_datetime'],
        'end_date' => $row['end_datetime'] !== null ? (string) $row['end_datetime'] : null,
        'end_datetime' => $row['end_datetime'] !== null ? (string) $row['end_datetime'] : null,
        'registration_deadline' => $row['registration_deadline'] !== null ? (string) $row['registration_deadline'] : null,
        'capacity' => $maxAttendees,
        'max_attendees' => $maxAttendees,
        'registered_count' => $registeredCount,
        'available_slots' => $availableSlots,
        'is_registered' => $isRegistered,
        'created_at' => (string) $row['created_at'],
        'updated_at' => (string) $row['updated_at'],
    ];

    json_success('Event details retrieved successfully.', $eventData);
}

function register_for_event(int $eventId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    try {
        $connection->begin_transaction();

        $profile = graduate_events_profile($connection, $authenticated['user_id']);
        $graduateProfileId = (int) $profile['graduate_profile_id'];

        $eventStatement = $connection->prepare(
            'SELECT event_id, is_active, max_attendees, registration_deadline 
             FROM events 
             WHERE event_id = ? 
             LIMIT 1'
        );
        $eventStatement->bind_param('i', $eventId);
        $eventStatement->execute();
        $event = $eventStatement->get_result()->fetch_assoc();
        $eventStatement->close();

        if (!is_array($event) || (int) $event['is_active'] !== 1) {
            rollback_transaction($connection);
            json_error('The requested event was not found.', [], 404);
        }

        if ($event['registration_deadline'] !== null) {
            $deadline = new DateTimeImmutable((string) $event['registration_deadline']);
            $now = new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi'));
            if ($now > $deadline) {
                rollback_transaction($connection);
                json_error('Validation failed.', ['event' => 'The registration deadline for this event has passed.'], 422);
            }
        }

        $checkStatement = $connection->prepare(
            'SELECT event_registration_id 
             FROM event_registrations 
             WHERE event_id = ? AND graduate_profile_id = ? 
             LIMIT 1'
        );
        $checkStatement->bind_param('ii', $eventId, $graduateProfileId);
        $checkStatement->execute();
        $existing = $checkStatement->get_result()->fetch_assoc();
        $checkStatement->close();

        if (is_array($existing)) {
            rollback_transaction($connection);
            json_error('Validation failed.', ['event' => 'You are already registered for this event.'], 422);
        }

        if ($event['max_attendees'] !== null) {
            $maxAttendees = (int) $event['max_attendees'];
            $countStatement = $connection->prepare(
                'SELECT COUNT(*) AS total 
                 FROM event_registrations 
                 WHERE event_id = ?'
            );
            $countStatement->bind_param('i', $eventId);
            $countStatement->execute();
            $countResult = $countStatement->get_result()->fetch_assoc();
            $countStatement->close();
            
            $currentAttendees = is_array($countResult) ? (int) $countResult['total'] : 0;
            if ($currentAttendees >= $maxAttendees) {
                rollback_transaction($connection);
                json_error('Validation failed.', ['event' => 'This event has reached its maximum registration capacity.'], 422);
            }
        }

        $registrationStatus = 'Registered';
        $insertStatement = $connection->prepare(
            'INSERT INTO event_registrations (event_id, graduate_profile_id, registration_status) 
             VALUES (?, ?, ?)'
        );
        $insertStatement->bind_param('iis', $eventId, $graduateProfileId, $registrationStatus);
        $insertStatement->execute();
        $registrationId = $connection->insert_id;
        $insertStatement->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $registration = graduate_event_registration($connection, $graduateProfileId, $registrationId);
    if ($registration !== null) {
        $registration['event_registration_id'] = (int) $registration['event_registration_id'];
        $registration['event_id'] = (int) $registration['event_id'];
        $registration['graduate_profile_id'] = (int) $registration['graduate_profile_id'];
        $registration['attended'] = (int) $registration['attended'];
    }

    json_success('Registered for event successfully.', $registration, 201);
}

function cancel_event_registration(int $eventId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    try {
        $connection->begin_transaction();

        $profile = graduate_events_profile($connection, $authenticated['user_id']);
        $graduateProfileId = (int) $profile['graduate_profile_id'];

        $eventStatement = $connection->prepare('SELECT event_id FROM events WHERE event_id = ? LIMIT 1');
        $eventStatement->bind_param('i', $eventId);
        $eventStatement->execute();
        $event = $eventStatement->get_result()->fetch_assoc();
        $eventStatement->close();

        if (!is_array($event)) {
            rollback_transaction($connection);
            json_error('The requested event was not found.', [], 404);
        }

        $checkStatement = $connection->prepare(
            'SELECT event_registration_id 
             FROM event_registrations 
             WHERE event_id = ? AND graduate_profile_id = ? 
             LIMIT 1'
        );
        $checkStatement->bind_param('ii', $eventId, $graduateProfileId);
        $checkStatement->execute();
        $existing = $checkStatement->get_result()->fetch_assoc();
        $checkStatement->close();

        if (!is_array($existing)) {
            rollback_transaction($connection);
            json_error('Event registration was not found.', [], 404);
        }

        $deleteStatement = $connection->prepare(
            'DELETE FROM event_registrations 
             WHERE event_id = ? AND graduate_profile_id = ?'
        );
        $deleteStatement->bind_param('ii', $eventId, $graduateProfileId);
        $deleteStatement->execute();
        $deleteStatement->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Event registration cancelled successfully.');
}

function list_available_events(): never
{
    list_upcoming_events();
}

function list_registered_events(): never
{
    list_upcoming_events();
}

function register_graduate_event(int $eventId): never
{
    register_for_event($eventId);
}

function cancel_graduate_event_registration(int $eventId): never
{
    cancel_event_registration($eventId);
}
