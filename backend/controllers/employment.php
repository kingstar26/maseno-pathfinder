<?php

declare(strict_types=1);

/** @return array{present: bool, value: ?string} */
function employment_optional_text(array $input, string $field, int $maximumLength): array
{
    if (!array_key_exists($field, $input)) return ['present' => false, 'value' => null];
    $value = $input[$field];
    if ($value === null) return ['present' => true, 'value' => null];
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        json_error('Validation failed.', [$field => 'This field must be text.'], 422);
    }
    $value = trim((string) $value);
    if ($value === '') return ['present' => true, 'value' => null];
    if (mb_strlen($value) > $maximumLength) {
        json_error('Validation failed.', [$field => "This field cannot exceed {$maximumLength} characters."], 422);
    }
    return ['present' => true, 'value' => $value];
}

/** @return array{present: bool, value: ?string} */
function employment_optional_date(array $input, string $field): array
{
    $date = employment_optional_text($input, $field, 10);
    if (!$date['present'] || $date['value'] === null) return $date;
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date['value']);
    $errors = DateTimeImmutable::getLastErrors();
    if ($parsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $parsed->format('Y-m-d') !== $date['value']) {
        json_error('Validation failed.', [$field => 'This field must be a valid date in YYYY-MM-DD format.'], 422);
    }
    return $date;
}

/** @return array{present: bool, value: ?float} */
function employment_optional_decimal(array $input, string $field): array
{
    if (!array_key_exists($field, $input)) return ['present' => false, 'value' => null];
    $value = $input[$field];
    if ($value === null) return ['present' => true, 'value' => null];
    if (is_int($value) || is_float($value)) $value = (string) $value;
    if (!is_string($value)) {
        json_error('Validation failed.', [$field => 'This field must be a valid monetary amount.'], 422);
    }
    $value = trim($value);
    if ($value === '') return ['present' => true, 'value' => null];
    if (preg_match('/^\d+(?:\.\d{1,2})?$/', $value) !== 1 || (float) $value > 9999999999.99) {
        json_error('Validation failed.', [$field => 'This field must be a valid monetary amount.'], 422);
    }
    return ['present' => true, 'value' => (float) $value];
}

/** @return array{present: bool, value: int} */
function employment_optional_current_job(array $input): array
{
    if (!array_key_exists('current_job', $input)) return ['present' => false, 'value' => 0];
    $value = $input['current_job'];
    if (is_bool($value)) return ['present' => true, 'value' => $value ? 1 : 0];
    if ($value === 0 || $value === 1 || $value === '0' || $value === '1') return ['present' => true, 'value' => (int) $value];
    json_error('Validation failed.', ['current_job' => 'This field must be a boolean.'], 422);
}

function graduate_employment_profile_id(mysqli $connection, int $userId): int
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

function validate_employment_status(mysqli $connection, int $statusId): void
{
    $statement = $connection->prepare('SELECT employment_status_id FROM employment_statuses WHERE employment_status_id = ? AND is_active = 1 LIMIT 1');
    $statement->bind_param('i', $statusId);
    $statement->execute();
    $status = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($status)) {
        json_error('Validation failed.', ['employment_status_id' => 'Select a valid employment status.'], 422);
    }
}

/** @return array<string, mixed>|null */
function graduate_employment_record(mysqli $connection, int $graduateProfileId, int $recordId): ?array
{
    $statement = $connection->prepare(
        'SELECT 
            er.employment_record_id,
            er.graduate_profile_id,
            er.employment_status_id,
            es.code AS employment_status_code,
            es.name AS employment_status_name,
            er.company_name,
            er.job_title,
            er.industry,
            er.employment_type,
            er.start_date,
            er.end_date,
            er.current_job,
            er.monthly_income,
            er.created_at,
            er.updated_at
        FROM employment_records AS er
        INNER JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
        WHERE er.employment_record_id = ? AND er.graduate_profile_id = ?
        LIMIT 1'
    );
    $statement->bind_param('ii', $recordId, $graduateProfileId);
    $statement->execute();
    $record = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($record)) return null;

    $record['employment_record_id'] = (int) $record['employment_record_id'];
    $record['graduate_profile_id'] = (int) $record['graduate_profile_id'];
    $record['employment_status_id'] = (int) $record['employment_status_id'];
    $record['current_job'] = (int) $record['current_job'];

    return $record;
}

/** @param array<string, mixed> $input */
function validate_employment_input(array $input, bool $creating): array
{
    $allowed = ['employment_status_id', 'company_name', 'job_title', 'industry', 'employment_type', 'start_date', 'end_date', 'current_job', 'monthly_income'];
    foreach ($input as $field => $_) {
        if (!in_array($field, $allowed, true)) {
            json_error('Validation failed.', [$field => 'This field is not supported.'], 422);
        }
    }
    if (!$creating && $input === []) {
        json_error('Validation failed.', ['request' => 'Provide at least one field to update.'], 422);
    }
    return [
        'status' => array_key_exists('employment_status_id', $input) ? required_integer($input, 'employment_status_id', 1, PHP_INT_MAX) : 0,
        'company' => employment_optional_text($input, 'company_name', 200),
        'jobTitle' => employment_optional_text($input, 'job_title', 150),
        'industry' => employment_optional_text($input, 'industry', 150),
        'employmentType' => employment_optional_text($input, 'employment_type', 100),
        'startDate' => employment_optional_date($input, 'start_date'),
        'endDate' => employment_optional_date($input, 'end_date'),
        'currentJob' => employment_optional_current_job($input),
        'monthlyIncome' => employment_optional_decimal($input, 'monthly_income'),
    ];
}

function list_employment_statuses(): never
{
    $connection = database_connection();
    $statement = $connection->prepare('SELECT employment_status_id, code, name FROM employment_statuses WHERE is_active = 1 ORDER BY name');
    $statement->execute();
    $result = $statement->get_result();
    $statuses = [];
    while ($row = $result->fetch_assoc()) {
        $row['employment_status_id'] = (int) $row['employment_status_id'];
        $statuses[] = $row;
    }
    $statement->close();
    json_success('Employment statuses retrieved successfully.', $statuses);
}

function list_graduate_employment(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $graduateProfileId = graduate_employment_profile_id($connection, $authenticated['user_id']);

    $statement = $connection->prepare(
        'SELECT 
            er.employment_record_id,
            er.graduate_profile_id,
            er.employment_status_id,
            es.code AS employment_status_code,
            es.name AS employment_status_name,
            er.company_name,
            er.job_title,
            er.industry,
            er.employment_type,
            er.start_date,
            er.end_date,
            er.current_job,
            er.monthly_income,
            er.created_at,
            er.updated_at
        FROM employment_records AS er
        INNER JOIN employment_statuses AS es ON es.employment_status_id = er.employment_status_id
        WHERE er.graduate_profile_id = ?
        ORDER BY er.current_job DESC, er.start_date DESC, er.employment_record_id DESC'
    );
    $statement->bind_param('i', $graduateProfileId);
    $statement->execute();
    $result = $statement->get_result();
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $row['employment_record_id'] = (int) $row['employment_record_id'];
        $row['graduate_profile_id'] = (int) $row['graduate_profile_id'];
        $row['employment_status_id'] = (int) $row['employment_status_id'];
        $row['current_job'] = (int) $row['current_job'];
        $records[] = $row;
    }
    $statement->close();
    json_success('Employment records retrieved successfully.', $records);
}

function create_graduate_employment(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $input = request_json_body();

    if (!array_key_exists('employment_status_id', $input)) {
        json_error('Validation failed.', ['employment_status_id' => 'This field is required.'], 422);
    }

    $employment = validate_employment_input($input, true);
    $connection = database_connection();

    $connection->begin_transaction();
    try {
        $graduateProfileId = graduate_employment_profile_id($connection, $authenticated['user_id']);
        $existingStatement = $connection->prepare('SELECT employment_record_id FROM employment_records WHERE graduate_profile_id = ? LIMIT 1 FOR UPDATE');
        $existingStatement->bind_param('i', $graduateProfileId);
        $existingStatement->execute();
        $existingRecord = $existingStatement->get_result()->fetch_assoc();
        $existingStatement->close();
        if (is_array($existingRecord)) {
            rollback_transaction($connection);
            json_error('You already have an active employment profile. Please update your existing employment information.', ['employment' => 'You already have an active employment profile. Please update your existing employment information.'], 422);
        }
        validate_employment_status($connection, $employment['status']);

        $currentJob = $employment['currentJob']['present'] ? $employment['currentJob']['value'] : 0;

        $statement = $connection->prepare(
            'INSERT INTO employment_records (
                graduate_profile_id,
                employment_status_id,
                company_name,
                job_title,
                industry,
                employment_type,
                start_date,
                end_date,
                current_job,
                monthly_income
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param(
            'iissssssid',
            $graduateProfileId,
            $employment['status'],
            $employment['company']['value'],
            $employment['jobTitle']['value'],
            $employment['industry']['value'],
            $employment['employmentType']['value'],
            $employment['startDate']['value'],
            $employment['endDate']['value'],
            $currentJob,
            $employment['monthlyIncome']['value']
        );
        $statement->execute();
        $recordId = $connection->insert_id;
        $statement->close();

        $createdRecord = graduate_employment_record($connection, $graduateProfileId, $recordId);
        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Employment record created successfully.', $createdRecord, 201);
}

function update_graduate_employment(int $recordId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $input = request_json_body();
    $employment = validate_employment_input($input, false);

    $connection = database_connection();

    $connection->begin_transaction();
    try {
        $graduateProfileId = graduate_employment_profile_id($connection, $authenticated['user_id']);

        $existing = graduate_employment_record($connection, $graduateProfileId, $recordId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Employment record was not found.', [], 404);
        }

        if (array_key_exists('employment_status_id', $input)) {
            validate_employment_status($connection, $employment['status']);
        }

        $hasStatus = array_key_exists('employment_status_id', $input) ? 1 : 0;

        $statement = $connection->prepare(
            'UPDATE employment_records SET
                employment_status_id = CASE WHEN ? THEN ? ELSE employment_status_id END,
                company_name = CASE WHEN ? THEN ? ELSE company_name END,
                job_title = CASE WHEN ? THEN ? ELSE job_title END,
                industry = CASE WHEN ? THEN ? ELSE industry END,
                employment_type = CASE WHEN ? THEN ? ELSE employment_type END,
                start_date = CASE WHEN ? THEN ? ELSE start_date END,
                end_date = CASE WHEN ? THEN ? ELSE end_date END,
                current_job = CASE WHEN ? THEN ? ELSE current_job END,
                monthly_income = CASE WHEN ? THEN ? ELSE monthly_income END
            WHERE employment_record_id = ? AND graduate_profile_id = ?'
        );
        $statement->bind_param(
            'iiisisisisisisiiidii',
            $hasStatus,
            $employment['status'],
            $employment['company']['present'],
            $employment['company']['value'],
            $employment['jobTitle']['present'],
            $employment['jobTitle']['value'],
            $employment['industry']['present'],
            $employment['industry']['value'],
            $employment['employmentType']['present'],
            $employment['employmentType']['value'],
            $employment['startDate']['present'],
            $employment['startDate']['value'],
            $employment['endDate']['present'],
            $employment['endDate']['value'],
            $employment['currentJob']['present'],
            $employment['currentJob']['value'],
            $employment['monthlyIncome']['present'],
            $employment['monthlyIncome']['value'],
            $recordId,
            $graduateProfileId
        );
        $statement->execute();
        $statement->close();

        $updatedRecord = graduate_employment_record($connection, $graduateProfileId, $recordId);
        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Employment record updated successfully.', $updatedRecord);
}

function delete_graduate_employment(int $recordId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();

    $connection->begin_transaction();
    try {
        $graduateProfileId = graduate_employment_profile_id($connection, $authenticated['user_id']);

        $existing = graduate_employment_record($connection, $graduateProfileId, $recordId);
        if ($existing === null) {
            rollback_transaction($connection);
            json_error('Employment record was not found.', [], 404);
        }

        $statement = $connection->prepare('DELETE FROM employment_records WHERE employment_record_id = ? AND graduate_profile_id = ?');
        $statement->bind_param('ii', $recordId, $graduateProfileId);
        $statement->execute();
        $statement->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Employment record deleted successfully.');
}
