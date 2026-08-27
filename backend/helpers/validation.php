<?php

declare(strict_types=1);

/** @return array<string, mixed> */
function request_json_body(): array
{
    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') return [];
    try { $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR); } catch (JsonException) { json_error('The request body must be valid JSON.', [], 400); }
    if (!is_array($decoded) || array_is_list($decoded)) json_error('The JSON request body must be an object.', [], 400);
    return $decoded;
}

/** @param array<string, mixed> $input */
function required_string(array $input, string $field, int $maximumLength): string
{
    $value = $input[$field] ?? null;
    if (!is_string($value)) json_error('Validation failed.', [$field => 'This field is required.'], 422);
    $value = trim($value);
    if ($value === '' || mb_strlen($value) > $maximumLength) json_error('Validation failed.', [$field => 'This field is invalid.'], 422);
    return $value;
}

function validated_email(string $email): string
{
    $email = mb_strtolower(trim($email));
    if (mb_strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) json_error('Email validation failed.', ['email' => 'Please enter a valid email address (for example: user@gmail.com or user@company.co.ke).'], 422);
    return $email;
}

/** @param array<string, mixed> $input */
function required_integer(array $input, string $field, int $minimum, int $maximum): int
{
    $value = $input[$field] ?? null;
    if (is_int($value)) $integer = $value;
    elseif (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) $integer = (int) trim($value);
    else json_error('Validation failed.', [$field => 'This field must be a whole number.'], 422);
    if ($integer < $minimum || $integer > $maximum) json_error('Validation failed.', [$field => 'This field is invalid.'], 422);
    return $integer;
}

function validated_password(string $password, string $field = 'password'): string
{
    $valid = strlen($password) >= 8 && preg_match('/[A-Z]/', $password) === 1 && preg_match('/[a-z]/', $password) === 1 && preg_match('/\d/', $password) === 1 && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    if (!$valid) json_error('Validation failed.', [$field => 'Password must contain at least 8 characters, uppercase and lowercase letters, a number, and a special character.'], 422);
    return $password;
}

/** @param array<string, mixed> $input */
function optional_string(array $input, string $field, int $maximumLength): ?string
{
    if (!array_key_exists($field, $input) || $input[$field] === null) {
        return null;
    }
    $value = $input[$field];
    if (!is_string($value)) {
        json_error('Validation failed.', [$field => 'This field must be a string.'], 422);
    }
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (mb_strlen($value) > $maximumLength) {
        json_error('Validation failed.', [$field => 'This field is invalid.'], 422);
    }
    return $value;
}

function validated_kenyan_phone(string $phone, string $field = 'phone_number'): string
{
    $phone = trim($phone);
    if (!preg_match('/^(?:\+254[71]|0[71])\d{8}$/', $phone)) {
        json_error('Phone number validation failed.', [$field => 'Enter a valid Kenyan phone number. The number must start with +2547, +2541, 07 or 01 followed by exactly eight additional digits.'], 422);
    }
    return $phone;
}

function validated_county_name(string $county, string $field = 'county'): string
{
    $county = trim($county);
    if (mb_strlen($county) > 100 || !preg_match("/^[A-Za-z '\-]+$/", $county)) {
        json_error('County validation failed.', [$field => 'County names may only contain letters, spaces, hyphens and apostrophes.'], 422);
    }
    return $county;
}

function validated_sub_county_name(string $subCounty, string $field = 'sub_county'): string
{
    $subCounty = trim($subCounty);
    if (mb_strlen($subCounty) > 100 || !preg_match("/^[A-Za-z '\-]+$/", $subCounty)) {
        json_error('Sub County validation failed.', [$field => 'Sub County names may only contain letters, spaces, hyphens and apostrophes.'], 422);
    }
    return $subCounty;
}

function validated_postal_address(string $address, string $field = 'postal_address'): string
{
    $address = trim($address);
    if (mb_strlen($address) > 255 || !preg_match('/^(?=.*[A-Za-z0-9])[A-Za-z0-9 .\-\/,]+$/', $address)) {
        json_error('Postal address validation failed.', [$field => 'Postal address contains invalid characters or format. Only letters, numbers, spaces, periods, hyphens, slashes and commas are allowed.'], 422);
    }
    return $address;
}

function validated_physical_address(string $address, string $field = 'physical_address'): string
{
    $address = trim($address);
    if (mb_strlen($address) > 255 || !preg_match('/^(?=.*[A-Za-z0-9])[A-Za-z0-9 .\-\/,]+$/', $address)) {
        json_error('Physical address validation failed.', [$field => 'Physical address contains invalid characters or format. Only letters, numbers, spaces, commas, periods, hyphens and slashes are allowed.'], 422);
    }
    return $address;
}

function validated_year_of_admission($year, string $field = 'year_of_admission'): int
{
    $strYear = trim((string) $year);
    if (!preg_match('/^\d{4}$/', $strYear)) {
        json_error('Year of admission validation failed.', [$field => 'Year of admission must contain exactly four numeric digits (for example: 2022).'], 422);
    }
    $intYear = (int) $strYear;
    if ($intYear < 1950 || $intYear > ((int) gmdate('Y') + 5)) {
        json_error('Year of admission validation failed.', [$field => 'Year of admission must contain exactly four numeric digits (for example: 2022).'], 422);
    }
    return $intYear;
}

function validated_year_of_graduation($year, string $field = 'year_of_graduation'): int
{
    $strYear = trim((string) $year);
    if (!preg_match('/^\d{4}$/', $strYear)) {
        json_error('Year of graduation validation failed.', [$field => 'Year of graduation must contain exactly four numeric digits (for example: 2022).'], 422);
    }
    $intYear = (int) $strYear;
    if ($intYear < 1950 || $intYear > ((int) gmdate('Y') + 5)) {
        json_error('Year of graduation validation failed.', [$field => 'Year of graduation must contain exactly four numeric digits (for example: 2022).'], 422);
    }
    return $intYear;
}

function validated_name(string $name, string $field = 'name'): string
{
    $name = trim($name);
    $length = mb_strlen($name);
    if ($length < 2 || $length > 50 || !preg_match('/^[A-Za-z]+(?:[ \'\-][A-Za-z]+)*$/', $name)) {
        json_error('Validation failed.', [$field => 'Names accept letters, spaces, hyphens and apostrophes only (2 to 50 characters).'], 422);
    }
    return $name;
}


