<?php

declare(strict_types=1);

function graduate_recommendation_profile_id(mysqli $connection, int $userId): int
{
    $statement = $connection->prepare('SELECT graduate_profile_id FROM graduate_profiles WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $profile = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($profile)) {
        json_error('Graduate profile was not found.', [], 404);
    }
    return (int) $profile['graduate_profile_id'];
}

function validate_recommendation_request_deadline(string $purpose): void
{
    $parts = preg_split('/\s*\|\s*/', $purpose) ?: [];
    $timezone = new DateTimeZone('Africa/Nairobi');
    $today = new DateTimeImmutable('today', $timezone);

    foreach ($parts as $part) {
        if (preg_match('/^Deadline:\s*(.*)$/i', $part, $match) !== 1) {
            continue;
        }

        $deadline = trim((string) $match[1]);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $deadline, $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        if (
            $deadline === ''
            || $parsed === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $parsed->format('Y-m-d') !== $deadline
            || $parsed < $today
        ) {
            json_error('Validation failed.', ['deadline' => 'Deadline date must be today or a future date.'], 422);
        }
    }
}

/** @return array<string, mixed>|null */
function fetch_graduate_recommendation_request(mysqli $connection, int $graduateProfileId, int $requestId): ?array
{
    $statement = $connection->prepare(
        'SELECT 
            rr.recommendation_request_id,
            rr.graduate_profile_id,
            rr.purpose,
            rr.request_status,
            rr.requested_at,
            rr.processed_at,
            rr.processed_by,
            rr.created_at,
            rr.updated_at
         FROM recommendation_requests AS rr
         WHERE rr.recommendation_request_id = ? AND rr.graduate_profile_id = ?
         LIMIT 1'
    );
    $statement->bind_param('ii', $requestId, $graduateProfileId);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($row)) return null;

    $reqId = (int) $row['recommendation_request_id'];

    $letterStmt = $connection->prepare(
        'SELECT 
            rl.recommendation_letter_id,
            rl.recommendation_request_id,
            rl.issued_by,
            rl.file_id,
            rl.letter_title,
            rl.issued_date,
            rl.created_at,
            rl.updated_at,
            f.original_filename,
            f.mime_type,
            f.byte_size,
            f.storage_key
         FROM recommendation_letters AS rl
         LEFT JOIN files AS f ON f.file_id = rl.file_id
         WHERE rl.recommendation_request_id = ?
         ORDER BY rl.issued_date DESC, rl.recommendation_letter_id DESC'
    );
    $letterStmt->bind_param('i', $reqId);
    $letterStmt->execute();
    $rawLetters = $letterStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $letterStmt->close();

    $letters = [];
    $primaryLetterFile = null;

    foreach ($rawLetters as $lRow) {
        $letterItem = [
            'recommendation_letter_id' => (int) $lRow['recommendation_letter_id'],
            'recommendation_request_id' => (int) $lRow['recommendation_request_id'],
            'issued_by' => (int) $lRow['issued_by'],
            'file_id' => $lRow['file_id'] !== null ? (int) $lRow['file_id'] : null,
            'letter_title' => $lRow['letter_title'] !== null ? (string) $lRow['letter_title'] : null,
            'issued_date' => (string) $lRow['issued_date'],
            'created_at' => (string) $lRow['created_at'],
            'updated_at' => (string) $lRow['updated_at'],
            'original_filename' => $lRow['original_filename'] !== null ? (string) $lRow['original_filename'] : null,
            'mime_type' => $lRow['mime_type'] !== null ? (string) $lRow['mime_type'] : null,
            'byte_size' => $lRow['byte_size'] !== null ? (int) $lRow['byte_size'] : null,
            'download_url' => '/graduate/recommendations/' . $reqId . '/download',
        ];
        $letters[] = $letterItem;
        if ($primaryLetterFile === null) {
            $primaryLetterFile = $letterItem;
        }
    }

    return [
        'request_id' => $reqId,
        'recommendation_request_id' => $reqId,
        'graduate_profile_id' => (int) $row['graduate_profile_id'],
        'purpose' => (string) $row['purpose'],
        'status' => (string) $row['request_status'],
        'request_status' => (string) $row['request_status'],
        'request_date' => (string) $row['requested_at'],
        'requested_at' => (string) $row['requested_at'],
        'approved_date' => $row['processed_at'] !== null ? (string) $row['processed_at'] : null,
        'processed_at' => $row['processed_at'] !== null ? (string) $row['processed_at'] : null,
        'processed_by' => $row['processed_by'] !== null ? (int) $row['processed_by'] : null,
        'letter_file' => $primaryLetterFile,
        'letters' => $letters,
        'created_at' => (string) $row['created_at'],
        'updated_at' => (string) $row['updated_at'],
    ];
}

function list_graduate_recommendations(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $graduateProfileId = graduate_recommendation_profile_id($connection, $authenticated['user_id']);

    $statement = $connection->prepare(
        'SELECT recommendation_request_id FROM recommendation_requests
         WHERE graduate_profile_id = ?
         ORDER BY requested_at DESC, recommendation_request_id DESC'
    );
    $statement->bind_param('i', $graduateProfileId);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $requests = [];
    foreach ($rows as $r) {
        $req = fetch_graduate_recommendation_request($connection, $graduateProfileId, (int) $r['recommendation_request_id']);
        if ($req !== null) {
            $requests[] = $req;
        }
    }

    json_success('Recommendation requests retrieved successfully.', $requests);
}

function create_recommendation_request(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $input = request_json_body();
    $purpose = required_string($input, 'purpose', 65535);
    validate_recommendation_request_deadline($purpose);

    $connection = database_connection();
    $graduateProfileId = graduate_recommendation_profile_id($connection, $authenticated['user_id']);

    $dupStmt = $connection->prepare(
        'SELECT recommendation_request_id FROM recommendation_requests
         WHERE graduate_profile_id = ? AND LOWER(TRIM(purpose)) = LOWER(TRIM(?)) LIMIT 1'
    );
    $dupStmt->bind_param('is', $graduateProfileId, $purpose);
    $dupStmt->execute();
    $dupRow = $dupStmt->get_result()->fetch_assoc();
    $dupStmt->close();

    if (is_array($dupRow)) {
        json_error('This recommendation request has already been submitted.', ['purpose' => 'This recommendation request has already been submitted.'], 422);
    }

    $connection->begin_transaction();
    try {
        $statement = $connection->prepare('INSERT INTO recommendation_requests (graduate_profile_id, purpose) VALUES (?, ?)');
        $statement->bind_param('is', $graduateProfileId, $purpose);
        $statement->execute();
        $requestId = $connection->insert_id;
        $statement->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $createdRequest = fetch_graduate_recommendation_request($connection, $graduateProfileId, $requestId);
    json_success('Recommendation request created successfully.', $createdRequest, 201);
}

function create_graduate_recommendation(): never
{
    create_recommendation_request();
}

function get_recommendation_details(int $requestId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $graduateProfileId = graduate_recommendation_profile_id($connection, $authenticated['user_id']);

    $request = fetch_graduate_recommendation_request($connection, $graduateProfileId, $requestId);
    if ($request === null) {
        json_error('Recommendation request was not found.', [], 404);
    }

    json_success('Recommendation request details retrieved successfully.', $request);
}

function download_recommendation_letter(int $requestId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate', 'management']);

    $connection = database_connection();
    if ($authenticated['role'] === 'graduate') {
        $graduateProfileId = graduate_recommendation_profile_id($connection, $authenticated['user_id']);
        $request = fetch_graduate_recommendation_request($connection, $graduateProfileId, $requestId);
    } else {
        $request = fetch_management_recommendation_request($connection, $requestId);
    }

    if ($request === null) {
        json_error('Recommendation request was not found.', [], 404);
    }

    if (strtolower((string) ($request['status'] ?? '')) !== 'approved') {
        json_error('Recommendation letter is only available for download when request status is Approved.', [], 400);
    }

    $letterStmt = $connection->prepare(
        'SELECT rl.recommendation_letter_id, rl.file_id, f.storage_key, f.original_filename, f.mime_type, f.byte_size
         FROM recommendation_letters AS rl
         INNER JOIN files AS f ON f.file_id = rl.file_id
         WHERE rl.recommendation_request_id = ?
         ORDER BY rl.issued_date DESC, rl.recommendation_letter_id DESC LIMIT 1'
    );
    $letterStmt->bind_param('i', $requestId);
    $letterStmt->execute();
    $letter = $letterStmt->get_result()->fetch_assoc();
    $letterStmt->close();

    if (!is_array($letter) || empty($letter['storage_key'])) {
        json_error('Recommendation letter is not available for download.', [], 400);
    }

    $filePath = __DIR__ . '/../uploads/' . $letter['storage_key'];
    if (!file_exists($filePath)) {
        $filePath = __DIR__ . '/../' . $letter['storage_key'];
    }

    if (!file_exists($filePath)) {
        json_error('Recommendation letter file could not be found on server.', [], 404);
    }

    $filename = $letter['original_filename'] ?: 'recommendation_letter.pdf';
    $mimeType = $letter['mime_type'] ?: 'application/pdf';
    $byteSize = (int) $letter['byte_size'];

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    if ($byteSize > 0) {
        header('Content-Length: ' . $byteSize);
    }
    readfile($filePath);
    exit;
}

function update_graduate_recommendation(int $requestId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $input = request_json_body();
    $purpose = required_string($input, 'purpose', 65535);
    validate_recommendation_request_deadline($purpose);

    $connection = database_connection();
    try {
        $connection->begin_transaction();
        $graduateProfileId = graduate_recommendation_profile_id($connection, $authenticated['user_id']);

        $existing = fetch_graduate_recommendation_request($connection, $graduateProfileId, $requestId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Recommendation request was not found.', [], 404);
        }

        $statement = $connection->prepare('UPDATE recommendation_requests SET purpose = ? WHERE recommendation_request_id = ? AND graduate_profile_id = ?');
        $statement->bind_param('sii', $purpose, $requestId, $graduateProfileId);
        $statement->execute();
        $statement->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $updatedRequest = fetch_graduate_recommendation_request($connection, $graduateProfileId, $requestId);
    json_success('Recommendation request updated successfully.', $updatedRequest);
}

function delete_graduate_recommendation(int $requestId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    try {
        $connection->begin_transaction();
        $graduateProfileId = graduate_recommendation_profile_id($connection, $authenticated['user_id']);

        $existing = fetch_graduate_recommendation_request($connection, $graduateProfileId, $requestId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Recommendation request was not found.', [], 404);
        }

        $statement = $connection->prepare('DELETE FROM recommendation_requests WHERE recommendation_request_id = ? AND graduate_profile_id = ?');
        $statement->bind_param('ii', $requestId, $graduateProfileId);
        $statement->execute();
        $statement->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Recommendation request deleted successfully.');
}
