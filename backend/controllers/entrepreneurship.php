<?php

declare(strict_types=1);

/** @return array<string, mixed> */
function graduate_entrepreneurship_profile(mysqli $connection, int $userId): array
{
    $statement = $connection->prepare('SELECT graduate_profile_id FROM graduate_profiles WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $profile = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($profile)) json_error('Graduate profile was not found.', [], 404);
    return $profile;
}

/** @return array{present: bool, value: int} */
function entrepreneurship_optional_integer(array $input, string $field, int $minimum, int $maximum): array
{
    if (!array_key_exists($field, $input)) return ['present' => false, 'value' => 0];
    return ['present' => true, 'value' => required_integer($input, $field, $minimum, $maximum)];
}

/** @return array{present: bool, value: ?int} */
function entrepreneurship_optional_year(array $input): array
{
    if (!array_key_exists('established_year', $input)) return ['present' => false, 'value' => null];
    $value = $input['established_year'];
    if ($value === '' || $value === null) return ['present' => true, 'value' => null];
    if (is_int($value)) $year = $value;
    elseif (is_string($value) && preg_match('/^\d{4}$/', trim($value)) === 1) $year = (int) trim($value);
    else json_error('Validation failed.', ['established_year' => 'This field must be a valid year.'], 422);
    if ($year < 1901 || $year > 2155) json_error('Validation failed.', ['established_year' => 'This field must be a valid year.'], 422);
    return ['present' => true, 'value' => $year];
}

/** @return array<string, mixed>|null */
function graduate_business_record(mysqli $connection, int $graduateProfileId, int $businessId): ?array
{
    $statement = $connection->prepare('SELECT business_id, graduate_profile_id, business_name, business_type, registration_number, location, employees_count, established_year, created_at, updated_at FROM businesses WHERE business_id = ? AND graduate_profile_id = ? LIMIT 1');
    $statement->bind_param('ii', $businessId, $graduateProfileId);
    $statement->execute();
    $business = $statement->get_result()->fetch_assoc();
    $statement->close();
    return is_array($business) ? $business : null;
}

/** @param array<string, mixed> $input */
function validate_entrepreneurship_input(array $input, bool $creating): array
{
    $allowed = ['business_name', 'business_type', 'registration_number', 'location', 'employees_count', 'established_year'];
    foreach ($input as $field => $_) if (!in_array($field, $allowed, true)) json_error('Validation failed.', [$field => 'This field is not supported.'], 422);
    if (!$creating && $input === []) json_error('Validation failed.', ['request' => 'Provide at least one field to update.'], 422);
    if ($creating || array_key_exists('business_name', $input)) $businessName = ['present' => true, 'value' => required_string($input, 'business_name', 200)];
    else $businessName = ['present' => false, 'value' => null];
    return [
        'businessName' => $businessName,
        'businessType' => optional_text($input, 'business_type', 150),
        'registrationNumber' => optional_text($input, 'registration_number', 100),
        'location' => optional_text($input, 'location', 200),
        'employeesCount' => entrepreneurship_optional_integer($input, 'employees_count', 0, 4294967295),
        'establishedYear' => entrepreneurship_optional_year($input),
    ];
}

function list_graduate_entrepreneurship(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $profile = graduate_entrepreneurship_profile($connection, $authenticated['user_id']);
    $graduateProfileId = (int) $profile['graduate_profile_id'];
    $statement = $connection->prepare('SELECT business_id, graduate_profile_id, business_name, business_type, registration_number, location, employees_count, established_year, created_at, updated_at FROM businesses WHERE graduate_profile_id = ? ORDER BY business_name, business_id');
    $statement->bind_param('i', $graduateProfileId);
    $statement->execute();
    $businesses = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    json_success('Business records retrieved successfully.', $businesses);
}

function create_graduate_entrepreneurship(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $business = validate_entrepreneurship_input(request_json_body(), true);
    $connection = database_connection();
    try {
        $connection->begin_transaction();
        $profile = graduate_entrepreneurship_profile($connection, $authenticated['user_id']);
        $graduateProfileId = (int) $profile['graduate_profile_id'];
        $employeesCount = $business['employeesCount']['present'] ? $business['employeesCount']['value'] : 0;
        $statement = $connection->prepare('INSERT INTO businesses (graduate_profile_id, business_name, business_type, registration_number, location, employees_count, established_year) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->bind_param('issssii', $graduateProfileId, $business['businessName']['value'], $business['businessType']['value'], $business['registrationNumber']['value'], $business['location']['value'], $employeesCount, $business['establishedYear']['value']);
        $statement->execute();
        $businessId = $connection->insert_id;
        $statement->close();
        $connection->commit();
    } catch (Throwable $exception) { rollback_transaction($connection); throw $exception; }
    json_success('Business record created successfully.', graduate_business_record($connection, $graduateProfileId, $businessId), 201);
}

function update_graduate_entrepreneurship(int $businessId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $business = validate_entrepreneurship_input(request_json_body(), false);
    $connection = database_connection();
    try {
        $connection->begin_transaction();
        $profile = graduate_entrepreneurship_profile($connection, $authenticated['user_id']);
        $graduateProfileId = (int) $profile['graduate_profile_id'];
        if (graduate_business_record($connection, $graduateProfileId, $businessId) === null) json_error('Business record was not found.', [], 404);
        $statement = $connection->prepare('UPDATE businesses SET business_name = CASE WHEN ? THEN ? ELSE business_name END, business_type = CASE WHEN ? THEN ? ELSE business_type END, registration_number = CASE WHEN ? THEN ? ELSE registration_number END, location = CASE WHEN ? THEN ? ELSE location END, employees_count = CASE WHEN ? THEN ? ELSE employees_count END, established_year = CASE WHEN ? THEN ? ELSE established_year END WHERE business_id = ? AND graduate_profile_id = ?');
        $statement->bind_param('isisisisiiiiii', $business['businessName']['present'], $business['businessName']['value'], $business['businessType']['present'], $business['businessType']['value'], $business['registrationNumber']['present'], $business['registrationNumber']['value'], $business['location']['present'], $business['location']['value'], $business['employeesCount']['present'], $business['employeesCount']['value'], $business['establishedYear']['present'], $business['establishedYear']['value'], $businessId, $graduateProfileId);
        $statement->execute();
        $statement->close();
        $connection->commit();
    } catch (Throwable $exception) { rollback_transaction($connection); throw $exception; }
    json_success('Business record updated successfully.', graduate_business_record($connection, $graduateProfileId, $businessId));
}

function delete_graduate_entrepreneurship(int $businessId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    try {
        $connection->begin_transaction();
        $profile = graduate_entrepreneurship_profile($connection, $authenticated['user_id']);
        $graduateProfileId = (int) $profile['graduate_profile_id'];
        if (graduate_business_record($connection, $graduateProfileId, $businessId) === null) json_error('Business record was not found.', [], 404);
        $statement = $connection->prepare('DELETE FROM businesses WHERE business_id = ? AND graduate_profile_id = ?');
        $statement->bind_param('ii', $businessId, $graduateProfileId);
        $statement->execute();
        $statement->close();
        $connection->commit();
    } catch (Throwable $exception) { rollback_transaction($connection); throw $exception; }
    json_success('Business record deleted successfully.');
}
