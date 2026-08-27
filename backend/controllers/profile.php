<?php

declare(strict_types=1);

/** @return array<string, mixed>|null */
function fetch_file_record(mysqli $connection, ?int $fileId): ?array
{
    if ($fileId === null || $fileId <= 0) return null;
    $statement = $connection->prepare(
        'SELECT file_id, uploaded_by_user_id, storage_key, original_filename, mime_type, byte_size, checksum_sha256, created_at
         FROM files
         WHERE file_id = ? LIMIT 1'
    );
    $statement->bind_param('i', $fileId);
    $statement->execute();
    $file = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($file)) return null;

    $file['file_id'] = (int) $file['file_id'];
    $file['uploaded_by_user_id'] = $file['uploaded_by_user_id'] !== null ? (int) $file['uploaded_by_user_id'] : null;
    $file['byte_size'] = (int) $file['byte_size'];

    return $file;
}

function calculate_profile_completion(mysqli $connection, int $userId): int
{
    $statement = $connection->prepare(
        'SELECT 
            u.first_name, u.last_name, u.email, u.avatar_file_id,
            gp.graduate_profile_id, gp.county, gp.sub_county, gp.postal_address, gp.physical_address, gp.cv_file_id,
            pp.phone_number, pp.gender, pp.date_of_birth
         FROM users AS u
         LEFT JOIN graduate_profiles AS gp ON gp.user_id = u.user_id
         LEFT JOIN person_profiles AS pp ON pp.user_id = u.user_id
         WHERE u.user_id = ? LIMIT 1'
    );
    $statement->bind_param('i', $userId);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($row)) return 0;

    $qualStmt = $connection->prepare(
        'SELECT COUNT(*) AS total FROM graduate_qualifications AS gq
         INNER JOIN graduate_profiles AS gp ON gp.graduate_profile_id = gq.graduate_profile_id
         WHERE gp.user_id = ?'
    );
    $qualStmt->bind_param('i', $userId);
    $qualStmt->execute();
    $qualRes = $qualStmt->get_result()->fetch_assoc();
    $qualStmt->close();
    $qualCount = is_array($qualRes) ? (int) $qualRes['total'] : 0;

    $totalFields = 10;
    $completed = 0;

    if (!empty(trim((string) ($row['first_name'] ?? '')))) $completed++;
    if (!empty(trim((string) ($row['last_name'] ?? '')))) $completed++;
    if (!empty(trim((string) ($row['phone_number'] ?? '')))) $completed++;
    if (!empty(trim((string) ($row['gender'] ?? '')))) $completed++;
    if (!empty(trim((string) ($row['date_of_birth'] ?? '')))) $completed++;
    if (!empty(trim((string) ($row['county'] ?? '')))) $completed++;
    if (!empty(trim((string) ($row['physical_address'] ?? '')))) $completed++;
    if ($row['avatar_file_id'] !== null) $completed++;
    if ($row['cv_file_id'] !== null) $completed++;
    if ($qualCount > 0) $completed++;

    return (int) round(($completed / $totalFields) * 100);
}

/** @return array<string, mixed> */
function fetch_graduate_profile_details(mysqli $connection, int $userId): array
{
    $statement = $connection->prepare(
        'SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.email,
            u.avatar_file_id,
            gp.graduate_profile_id,
            gp.county,
            gp.sub_county,
            gp.postal_address,
            gp.physical_address,
            gp.cv_file_id,
            pp.middle_name,
            pp.gender,
            pp.national_id,
            pp.date_of_birth,
            pp.phone_number
         FROM users AS u
         LEFT JOIN graduate_profiles AS gp ON gp.user_id = u.user_id
         LEFT JOIN person_profiles AS pp ON pp.user_id = u.user_id
         WHERE u.user_id = ? LIMIT 1'
    );
    $statement->bind_param('i', $userId);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($row)) {
        json_error('Graduate profile was not found.', [], 404);
    }

    $prefStmt = $connection->prepare(
        'SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ? AND preference_key = \'bio\' LIMIT 1'
    );
    $prefStmt->bind_param('i', $userId);
    $prefStmt->execute();
    $prefsResult = $prefStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $prefStmt->close();

    $bio = null;
    foreach ($prefsResult as $pref) {
        if ($pref['preference_key'] === 'bio') {
            $bio = $pref['preference_value'];
        }
    }

    $avatarFileId = $row['avatar_file_id'] !== null ? (int) $row['avatar_file_id'] : null;
    $avatarUrl = resolve_user_avatar_url($connection, $userId, $avatarFileId);
    $cvFileId = $row['cv_file_id'] !== null ? (int) $row['cv_file_id'] : null;

    $avatarFile = fetch_file_record($connection, $avatarFileId);
    $cvFile = fetch_file_record($connection, $cvFileId);

    $pwStmt = $connection->prepare('SELECT MAX(changed_at) AS password_changed_at FROM password_history WHERE user_id = ?');
    $pwStmt->bind_param('i', $userId);
    $pwStmt->execute();
    $pwRow = $pwStmt->get_result()->fetch_assoc();
    $pwStmt->close();
    $passwordChangedAt = is_array($pwRow) ? $pwRow['password_changed_at'] : null;

    $completionPercentage = calculate_profile_completion($connection, $userId);
    $graduateProfileId = $row['graduate_profile_id'] !== null ? (int) $row['graduate_profile_id'] : null;

    $qualRow = null;
    if ($graduateProfileId !== null) {
        $qualStmt = $connection->prepare(
            'SELECT gq.graduate_qualification_id, gq.programme_id, gq.admission_number, gq.registration_number,
                    gq.year_of_admission, gq.year_of_graduation, gq.degree_awarded, gq.graduation_status_id,
                    p.name AS programme_name, d.name AS department_name, f.name AS faculty_name, gs.name AS graduation_status_name
             FROM graduate_qualifications AS gq
             LEFT JOIN programmes AS p ON p.programme_id = gq.programme_id
             LEFT JOIN departments AS d ON d.department_id = p.department_id
             LEFT JOIN faculties AS f ON f.faculty_id = d.faculty_id
             LEFT JOIN graduation_statuses AS gs ON gs.graduation_status_id = gq.graduation_status_id
             WHERE gq.graduate_profile_id = ?
             ORDER BY gq.graduate_qualification_id DESC LIMIT 1'
        );
        $qualStmt->bind_param('i', $graduateProfileId);
        $qualStmt->execute();
        $qualRow = $qualStmt->get_result()->fetch_assoc();
        $qualStmt->close();
    }

    return [
        'user_id' => (int) $row['user_id'],
        'graduate_profile_id' => $graduateProfileId,
        'first_name' => (string) $row['first_name'],
        'last_name' => (string) $row['last_name'],
        'email' => (string) $row['email'],
        'password_changed_at' => $passwordChangedAt !== null ? (string) $passwordChangedAt : null,
        'middle_name' => $row['middle_name'] !== null ? (string) $row['middle_name'] : null,
        'gender' => $row['gender'] !== null ? (string) $row['gender'] : null,
        'national_id' => $row['national_id'] !== null ? (string) $row['national_id'] : null,
        'date_of_birth' => $row['date_of_birth'] !== null ? (string) $row['date_of_birth'] : null,
        'phone_number' => $row['phone_number'] !== null ? (string) $row['phone_number'] : null,
        'county' => $row['county'] !== null ? (string) $row['county'] : null,
        'sub_county' => $row['sub_county'] !== null ? (string) $row['sub_county'] : null,
        'postal_address' => $row['postal_address'] !== null ? (string) $row['postal_address'] : null,
        'physical_address' => $row['physical_address'] !== null ? (string) $row['physical_address'] : null,
        'programme' => is_array($qualRow) && isset($qualRow['programme_name']) ? (string) $qualRow['programme_name'] : null,
        'programme_id' => is_array($qualRow) && isset($qualRow['programme_id']) ? (int) $qualRow['programme_id'] : null,
        'department' => is_array($qualRow) && isset($qualRow['department_name']) ? (string) $qualRow['department_name'] : null,
        'faculty' => is_array($qualRow) && isset($qualRow['faculty_name']) ? (string) $qualRow['faculty_name'] : null,
        'admission_number' => is_array($qualRow) && isset($qualRow['admission_number']) ? (string) $qualRow['admission_number'] : null,
        'registration_number' => is_array($qualRow) && isset($qualRow['registration_number']) ? (string) $qualRow['registration_number'] : null,
        'year_of_admission' => is_array($qualRow) && $qualRow['year_of_admission'] !== null ? (int) $qualRow['year_of_admission'] : null,
        'year_of_graduation' => is_array($qualRow) && $qualRow['year_of_graduation'] !== null ? (int) $qualRow['year_of_graduation'] : null,
        'degree_awarded' => is_array($qualRow) && isset($qualRow['degree_awarded']) ? (string) $qualRow['degree_awarded'] : null,
        'graduation_status' => is_array($qualRow) && isset($qualRow['graduation_status_name']) ? (string) $qualRow['graduation_status_name'] : null,
        'bio' => $bio,
        'avatar_url' => $avatarUrl,
        'personal_details' => [
            'middle_name' => $row['middle_name'] !== null ? (string) $row['middle_name'] : null,
            'gender' => $row['gender'] !== null ? (string) $row['gender'] : null,
            'national_id' => $row['national_id'] !== null ? (string) $row['national_id'] : null,
            'date_of_birth' => $row['date_of_birth'] !== null ? (string) $row['date_of_birth'] : null,
            'phone_number' => $row['phone_number'] !== null ? (string) $row['phone_number'] : null,
        ],
        'profile_information' => [
            'graduate_profile_id' => $graduateProfileId,
            'county' => $row['county'] !== null ? (string) $row['county'] : null,
            'sub_county' => $row['sub_county'] !== null ? (string) $row['sub_county'] : null,
            'postal_address' => $row['postal_address'] !== null ? (string) $row['postal_address'] : null,
            'physical_address' => $row['physical_address'] !== null ? (string) $row['physical_address'] : null,
        ],
        'avatar_information' => [
            'avatar_file_id' => $avatarFileId,
            'file' => $avatarFile,
        ],
        'cv_information' => [
            'cv_file_id' => $cvFileId,
            'file' => $cvFile,
        ],
        'completion_percentage' => $completionPercentage,
        'profile_completion_percentage' => $completionPercentage,
    ];
}

function get_authenticated_user(): never
{
    $authenticated = require_authenticated_user();
    $connection = database_connection();

    $statement = $connection->prepare(
        'SELECT u.user_id, u.first_name, u.last_name, u.email, u.last_login_at, u.avatar_file_id, r.code AS role
         FROM users AS u
         INNER JOIN roles AS r ON r.role_id = u.role_id
         WHERE u.user_id = ? LIMIT 1'
    );
    $statement->bind_param('i', $authenticated['user_id']);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($user)) {
        json_error('Authenticated user was not found.', [], 404);
    }

    $avatarUrl = resolve_user_avatar_url($connection, (int)$user['user_id'], $user['avatar_file_id'] !== null ? (int)$user['avatar_file_id'] : null);
    $profile = fetch_graduate_profile_details($connection, $authenticated['user_id']);

    json_response(
        [
            'user' => [
                'id' => (string) $user['user_id'],
                'user_id' => (int) $user['user_id'],
                'first_name' => (string) $user['first_name'],
                'last_name' => (string) $user['last_name'],
                'email' => (string) $user['email'],
                'role' => (string) $user['role'],
                'avatar_url' => $avatarUrl,
                'last_login_at' => $user['last_login_at'] !== null ? (string) $user['last_login_at'] : null,
            ],
            'profile' => $profile,
        ],
        200
    );
}

function get_graduate_profile(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $profile = fetch_graduate_profile_details($connection, $authenticated['user_id']);
    json_success('Graduate profile retrieved successfully.', $profile);
}

function update_graduate_profile(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $input = request_json_body();

    $allowed = [
        'county',
        'sub_county',
        'postal_address',
        'physical_address',
        'first_name',
        'last_name',
        'phone_number',
        'gender',
        'date_of_birth',
        'avatar_url',
        'bio',
        'year_of_admission',
        'year_of_graduation',
        'degree_awarded',
        'admission_number',
        'registration_number',
        'programme',
        'graduation_status'
    ];
    foreach ($input as $field => $_) {
        if (!in_array($field, $allowed, true)) {
            json_error('Validation failed.', [$field => 'This field is not supported.'], 422);
        }
    }

    if (empty($input)) {
        json_error('Validation failed.', ['request' => 'Provide at least one field to update.'], 422);
    }

    $validationErrors = [];
    if (array_key_exists('county', $input) && $input['county'] !== null) {
        if (!is_string($input['county'])) {
            $validationErrors['county'] = 'County names may only contain letters, spaces, hyphens and apostrophes.';
        } else {
            $trimmedCounty = trim($input['county']);
            if ($trimmedCounty !== '' && (mb_strlen($trimmedCounty) > 100 || preg_match("/^[A-Za-z '\-]+$/", $trimmedCounty) !== 1)) {
                $validationErrors['county'] = 'County names may only contain letters, spaces, hyphens and apostrophes.';
            }
        }
    }
    if (array_key_exists('sub_county', $input) && $input['sub_county'] !== null) {
        if (!is_string($input['sub_county'])) {
            $validationErrors['sub_county'] = 'Sub County names may only contain letters, spaces, hyphens and apostrophes.';
        } else {
            $trimmedSub = trim($input['sub_county']);
            if ($trimmedSub !== '' && (mb_strlen($trimmedSub) > 100 || preg_match("/^[A-Za-z '\-]+$/", $trimmedSub) !== 1)) {
                $validationErrors['sub_county'] = 'Sub County names may only contain letters, spaces, hyphens and apostrophes.';
            }
        }
    }
    if (array_key_exists('postal_address', $input) && $input['postal_address'] !== null) {
        if (!is_string($input['postal_address'])) {
            $validationErrors['postal_address'] = 'Postal address contains invalid characters or format. Only letters, numbers, spaces, periods, hyphens, slashes and commas are allowed.';
        } else {
            $trimmedPostal = trim($input['postal_address']);
            if ($trimmedPostal !== '' && (mb_strlen($trimmedPostal) > 255 || preg_match('/^(?=.*[A-Za-z0-9])[A-Za-z0-9 .\-\/,]+$/', $trimmedPostal) !== 1)) {
                $validationErrors['postal_address'] = 'Postal address contains invalid characters or format. Only letters, numbers, spaces, periods, hyphens, slashes and commas are allowed.';
            }
        }
    }
    if (array_key_exists('physical_address', $input) && $input['physical_address'] !== null) {
        if (!is_string($input['physical_address'])) {
            $validationErrors['physical_address'] = 'Physical address contains invalid characters or format. Only letters, numbers, spaces, commas, periods, hyphens and slashes are allowed.';
        } else {
            $trimmedPhysical = trim($input['physical_address']);
            if ($trimmedPhysical !== '' && (mb_strlen($trimmedPhysical) > 255 || preg_match('/^(?=.*[A-Za-z0-9])[A-Za-z0-9 .\-\/,]+$/', $trimmedPhysical) !== 1)) {
                $validationErrors['physical_address'] = 'Physical address contains invalid characters or format. Only letters, numbers, spaces, commas, periods, hyphens and slashes are allowed.';
            }
        }
    }
    if (array_key_exists('first_name', $input) && $input['first_name'] !== null) {
        if (!is_string($input['first_name']) || trim($input['first_name']) === '') {
            $validationErrors['first_name'] = 'First name is required.';
        } elseif (mb_strlen(trim($input['first_name'])) > 100) {
            $validationErrors['first_name'] = 'First name cannot exceed 100 characters.';
        }
    }
    if (array_key_exists('last_name', $input) && $input['last_name'] !== null) {
        if (!is_string($input['last_name']) || trim($input['last_name']) === '') {
            $validationErrors['last_name'] = 'Last name is required.';
        } elseif (mb_strlen(trim($input['last_name'])) > 100) {
            $validationErrors['last_name'] = 'Last name cannot exceed 100 characters.';
        }
    }
    if (array_key_exists('phone_number', $input) && $input['phone_number'] !== null) {
        if (!is_string($input['phone_number'])) {
            $validationErrors['phone_number'] = 'Enter a valid Kenyan phone number. The number must start with +2547, +2541, 07 or 01 followed by exactly eight additional digits.';
        } else {
            $trimmedPhone = trim($input['phone_number']);
            if ($trimmedPhone !== '' && preg_match('/^(?:\+254[71]|0[71])\d{8}$/', $trimmedPhone) !== 1) {
                $validationErrors['phone_number'] = 'Enter a valid Kenyan phone number. The number must start with +2547, +2541, 07 or 01 followed by exactly eight additional digits.';
            }
        }
    }
    if (array_key_exists('year_of_admission', $input) && $input['year_of_admission'] !== null && trim((string)$input['year_of_admission']) !== '') {
        $strAdmYr = trim((string)$input['year_of_admission']);
        if (preg_match('/^\d{4}$/', $strAdmYr) !== 1) {
            $validationErrors['year_of_admission'] = 'Year of admission must contain exactly four numeric digits (for example: 2022).';
        }
    }
    if (array_key_exists('year_of_graduation', $input) && $input['year_of_graduation'] !== null && trim((string)$input['year_of_graduation']) !== '') {
        $strGradYr = trim((string)$input['year_of_graduation']);
        if (preg_match('/^\d{4}$/', $strGradYr) !== 1) {
            $validationErrors['year_of_graduation'] = 'Year of graduation must contain exactly four numeric digits (for example: 2022).';
        }
    }
    if (array_key_exists('gender', $input) && $input['gender'] !== null) {
        if (!is_string($input['gender'])) {
            $validationErrors['gender'] = 'Gender must be a string.';
        } elseif (mb_strlen(trim($input['gender'])) > 20) {
            $validationErrors['gender'] = 'Gender cannot exceed 20 characters.';
        }
    }
    if (array_key_exists('date_of_birth', $input) && $input['date_of_birth'] !== null) {
        if (!is_string($input['date_of_birth']) || preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($input['date_of_birth'])) !== 1) {
            $validationErrors['date_of_birth'] = 'Date of birth must be a valid date in YYYY-MM-DD format.';
        } else {
            $dobParsed = DateTimeImmutable::createFromFormat('!Y-m-d', trim($input['date_of_birth']));
            $errors = DateTimeImmutable::getLastErrors();
            if ($dobParsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                $validationErrors['date_of_birth'] = 'Date of birth must be a valid date in YYYY-MM-DD format.';
            }
        }
    }

    if (!empty($validationErrors)) {
        json_error('Validation failed.', $validationErrors, 422);
    }

    $connection = database_connection();
    $userId = $authenticated['user_id'];
    $isAdmin = ($authenticated['role'] ?? '') === 'administrator';

    $connection->begin_transaction();
    try {
        $checkGp = $connection->prepare('SELECT graduate_profile_id FROM graduate_profiles WHERE user_id = ? LIMIT 1');
        $checkGp->bind_param('i', $userId);
        $checkGp->execute();
        $gpRow = $checkGp->get_result()->fetch_assoc();
        $checkGp->close();

        if (!is_array($gpRow)) {
            $insGp = $connection->prepare('INSERT INTO graduate_profiles (user_id) VALUES (?)');
            $insGp->bind_param('i', $userId);
            $insGp->execute();
            $graduateProfileId = $connection->insert_id;
            $insGp->close();
        } else {
            $graduateProfileId = (int) $gpRow['graduate_profile_id'];
        }

        if (array_key_exists('county', $input) || array_key_exists('sub_county', $input) || array_key_exists('postal_address', $input) || array_key_exists('physical_address', $input)) {
            $hasCounty = array_key_exists('county', $input) ? 1 : 0;
            $countyVal = ($hasCounty && $input['county'] !== null) ? trim((string) $input['county']) : null;

            $hasSubCounty = array_key_exists('sub_county', $input) ? 1 : 0;
            $subCountyVal = ($hasSubCounty && $input['sub_county'] !== null) ? trim((string) $input['sub_county']) : null;

            $hasPostal = array_key_exists('postal_address', $input) ? 1 : 0;
            $postalVal = ($hasPostal && $input['postal_address'] !== null) ? trim((string) $input['postal_address']) : null;

            $hasPhysical = array_key_exists('physical_address', $input) ? 1 : 0;
            $physicalVal = ($hasPhysical && $input['physical_address'] !== null) ? trim((string) $input['physical_address']) : null;

            $statement = $connection->prepare(
                'UPDATE graduate_profiles SET
                    county           = CASE WHEN ? THEN ? ELSE county END,
                    sub_county       = CASE WHEN ? THEN ? ELSE sub_county END,
                    postal_address   = CASE WHEN ? THEN ? ELSE postal_address END,
                    physical_address = CASE WHEN ? THEN ? ELSE physical_address END
                 WHERE user_id = ?'
            );
            $statement->bind_param('isisisisi', $hasCounty, $countyVal, $hasSubCounty, $subCountyVal, $hasPostal, $postalVal, $hasPhysical, $physicalVal, $userId);
            $statement->execute();
            $statement->close();
        }

        // Login details (first_name, last_name, email) can ONLY be modified by Administrators
        if ($isAdmin && (array_key_exists('first_name', $input) || array_key_exists('last_name', $input))) {
            $hasFirstName = array_key_exists('first_name', $input) ? 1 : 0;
            $firstNameVal = $hasFirstName ? trim((string) $input['first_name']) : '';
            $hasLastName = array_key_exists('last_name', $input) ? 1 : 0;
            $lastNameVal = $hasLastName ? trim((string) $input['last_name']) : '';

            $statement = $connection->prepare(
                'UPDATE users SET
                    first_name = CASE WHEN ? THEN ? ELSE first_name END,
                    last_name  = CASE WHEN ? THEN ? ELSE last_name  END
                 WHERE user_id = ?'
            );
            $statement->bind_param('isisi', $hasFirstName, $firstNameVal, $hasLastName, $lastNameVal, $userId);
            $statement->execute();
            $statement->close();
        }

        if (array_key_exists('phone_number', $input) || array_key_exists('gender', $input) || array_key_exists('date_of_birth', $input)) {
            $checkPp = $connection->prepare('SELECT user_id FROM person_profiles WHERE user_id = ? LIMIT 1');
            $checkPp->bind_param('i', $userId);
            $checkPp->execute();
            $ppRow = $checkPp->get_result()->fetch_assoc();
            $checkPp->close();

            if (!is_array($ppRow)) {
                $insPp = $connection->prepare('INSERT INTO person_profiles (user_id) VALUES (?)');
                $insPp->bind_param('i', $userId);
                $insPp->execute();
                $insPp->close();
            }

            $hasPhone = array_key_exists('phone_number', $input) ? 1 : 0;
            $phoneVal = ($hasPhone && $input['phone_number'] !== null) ? trim((string) $input['phone_number']) : null;

            $hasGender = array_key_exists('gender', $input) ? 1 : 0;
            $genderVal = ($hasGender && $input['gender'] !== null) ? trim((string) $input['gender']) : null;

            $hasDob = array_key_exists('date_of_birth', $input) ? 1 : 0;
            $dobVal = ($hasDob && $input['date_of_birth'] !== null) ? trim((string) $input['date_of_birth']) : null;

            $statement = $connection->prepare(
                'UPDATE person_profiles SET
                    phone_number  = CASE WHEN ? THEN ? ELSE phone_number  END,
                    gender        = CASE WHEN ? THEN ? ELSE gender        END,
                    date_of_birth = CASE WHEN ? THEN ? ELSE date_of_birth END
                 WHERE user_id = ?'
            );
            $statement->bind_param('isisisi', $hasPhone, $phoneVal, $hasGender, $genderVal, $hasDob, $dobVal, $userId);
            $statement->execute();
            $statement->close();
        }

        // Academic qualifications updates
        if (array_key_exists('year_of_admission', $input) || array_key_exists('year_of_graduation', $input) || array_key_exists('degree_awarded', $input) || array_key_exists('admission_number', $input) || array_key_exists('registration_number', $input) || array_key_exists('programme', $input)) {
            $qCheck = $connection->prepare('SELECT graduate_qualification_id FROM graduate_qualifications WHERE graduate_profile_id = ? ORDER BY graduate_qualification_id DESC LIMIT 1');
            $qCheck->bind_param('i', $graduateProfileId);
            $qCheck->execute();
            $qRow = $qCheck->get_result()->fetch_assoc();
            $qCheck->close();

            $admYrVal = (array_key_exists('year_of_admission', $input) && $input['year_of_admission'] !== null && trim((string)$input['year_of_admission']) !== '') ? (int) $input['year_of_admission'] : null;
            $gradYrVal = (array_key_exists('year_of_graduation', $input) && $input['year_of_graduation'] !== null && trim((string)$input['year_of_graduation']) !== '') ? (int) $input['year_of_graduation'] : null;
            $degVal = (array_key_exists('degree_awarded', $input) && $input['degree_awarded'] !== null) ? trim((string)$input['degree_awarded']) : null;
            $admNoVal = (array_key_exists('admission_number', $input) && $input['admission_number'] !== null) ? trim((string)$input['admission_number']) : null;
            $regNoVal = (array_key_exists('registration_number', $input) && $input['registration_number'] !== null) ? trim((string)$input['registration_number']) : null;

            $progIdVal = null;
            if (array_key_exists('programme', $input) && !empty($input['programme'])) {
                $pStmt = $connection->prepare('SELECT programme_id FROM programmes WHERE name = ? LIMIT 1');
                $pName = trim((string)$input['programme']);
                $pStmt->bind_param('s', $pName);
                $pStmt->execute();
                $pRow = $pStmt->get_result()->fetch_assoc();
                $pStmt->close();
                if (is_array($pRow)) $progIdVal = (int) $pRow['programme_id'];
            }

            $hasAdmYr = array_key_exists('year_of_admission', $input) ? 1 : 0;
            $hasGradYr = array_key_exists('year_of_graduation', $input) ? 1 : 0;
            $hasDeg = array_key_exists('degree_awarded', $input) ? 1 : 0;
            $hasAdmNo = array_key_exists('admission_number', $input) ? 1 : 0;
            $hasRegNo = array_key_exists('registration_number', $input) ? 1 : 0;
            $hasProg = ($progIdVal !== null) ? 1 : 0;

            if (is_array($qRow)) {
                $gqId = (int) $qRow['graduate_qualification_id'];
                $uStmt = $connection->prepare(
                    'UPDATE graduate_qualifications SET
                        year_of_admission   = CASE WHEN ? THEN ? ELSE year_of_admission END,
                        year_of_graduation  = CASE WHEN ? THEN ? ELSE year_of_graduation END,
                        degree_awarded      = CASE WHEN ? THEN ? ELSE degree_awarded END,
                        admission_number    = CASE WHEN ? THEN ? ELSE admission_number END,
                        registration_number = CASE WHEN ? THEN ? ELSE registration_number END,
                        programme_id        = CASE WHEN ? THEN ? ELSE programme_id END
                     WHERE graduate_qualification_id = ?'
                );
                $uStmt->bind_param(
                    'iiiiisisisiii',
                    $hasAdmYr,
                    $admYrVal,
                    $hasGradYr,
                    $gradYrVal,
                    $hasDeg,
                    $degVal,
                    $hasAdmNo,
                    $admNoVal,
                    $hasRegNo,
                    $regNoVal,
                    $hasProg,
                    $progIdVal,
                    $gqId
                );
                $uStmt->execute();
                $uStmt->close();
            } elseif ($hasProg && $progIdVal !== null) {
                $iStmt = $connection->prepare(
                    'INSERT INTO graduate_qualifications (graduate_profile_id, programme_id, year_of_admission, year_of_graduation, degree_awarded, admission_number, registration_number) VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $iStmt->bind_param('iiiisss', $graduateProfileId, $progIdVal, $admYrVal, $gradYrVal, $degVal, $admNoVal, $regNoVal);
                $iStmt->execute();
                $iStmt->close();
            }
        }

        if (array_key_exists('avatar_url', $input)) {
            $avatarVal = $input['avatar_url'] !== null ? trim((string) $input['avatar_url']) : null;
            $prefKey = 'avatar_url';
            $stmt = $connection->prepare(
                'INSERT INTO user_preferences (user_id, preference_key, preference_value)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)'
            );
            $stmt->bind_param('iss', $userId, $prefKey, $avatarVal);
            $stmt->execute();
            $stmt->close();
        }

        if (array_key_exists('bio', $input)) {
            $bioVal = $input['bio'] !== null ? trim((string) $input['bio']) : null;
            $prefKey = 'bio';
            $stmt = $connection->prepare(
                'INSERT INTO user_preferences (user_id, preference_key, preference_value)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)'
            );
            $stmt->bind_param('iss', $userId, $prefKey, $bioVal);
            $stmt->execute();
            $stmt->close();
        }

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $updatedProfile = fetch_graduate_profile_details($connection, $userId);
    json_success('Graduate profile updated successfully.', $updatedProfile);
}

function upload_profile_avatar(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate', 'management', 'administrator']);

    $file = $_FILES['avatar'] ?? $_FILES['file'] ?? $_FILES['image'] ?? null;

    // --- Handle upload errors with specific messages ---
    if (!is_array($file)) {
        json_error('Profile photo upload failed.', ['avatar' => 'No file was uploaded. Please select an image.'], 422);
    }

    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errorCode !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The selected image exceeds the server\'s maximum file size limit. Please compress your photo and try again.',
            UPLOAD_ERR_FORM_SIZE  => 'The selected image exceeds the maximum file size allowed by the form.',
            UPLOAD_ERR_PARTIAL    => 'The image was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE    => 'No file was selected. Please choose an image to upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: temporary folder is missing. Contact system administrator.',
            UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write file to disk. Contact system administrator.',
            UPLOAD_ERR_EXTENSION  => 'Server error: a PHP extension stopped the upload. Contact system administrator.',
        ];
        $message = $errorMessages[$errorCode] ?? 'An unknown upload error occurred. Please try again.';
        json_error('Profile photo upload failed.', ['avatar' => $message], 422);
    }

    $originalName = (string) ($file['name'] ?? 'avatar.jpg');
    $tmpPath = (string) ($file['tmp_name'] ?? '');
    $byteSize = (int) ($file['size'] ?? 0);

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        json_error('Profile photo upload failed.', ['avatar' => 'The uploaded file could not be verified. Please choose a valid JPG, JPEG, PNG or WEBP image.'], 422);
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        json_error('Profile photo upload failed.', ['avatar' => 'The selected file is not a valid profile image. Please upload a JPG, JPEG, PNG or WEBP image.'], 422);
    }

    $mimeType = mime_content_type($tmpPath) ?: 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/pjpeg', 'image/webp'];
    if (!in_array(strtolower($mimeType), $allowedMimes, true)) {
        json_error('Profile photo upload failed.', ['avatar' => 'The selected file is not a valid profile image. Please upload a JPG, JPEG, PNG or WEBP image.'], 422);
    }

    $connection = database_connection();
    $userId = $authenticated['user_id'];

    // --- Ensure the upload directory exists and is writable ---
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            json_error('Profile photo upload failed.', ['avatar' => 'The image could not be saved because the storage directory is unavailable. Please contact the system administrator.'], 500);
        }
    }
    if (!is_writable($uploadDir)) {
        json_error('Profile photo upload failed.', ['avatar' => 'The image could not be saved because the storage directory is not writable. Please contact the system administrator.'], 500);
    }

    $storageKey = 'avatars/user_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . basename($storageKey);

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        json_error('Profile photo upload failed.', ['avatar' => 'The image could not be saved due to a server error (e.g., disk full or permission issue). Please contact support.'], 500);
    }

    $checksum = hash_file('sha256', $targetPath) ?: null;

    $connection->begin_transaction();
    try {
        $stmt = $connection->prepare(
            'INSERT INTO files (uploaded_by_user_id, storage_key, original_filename, mime_type, byte_size, checksum_sha256)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssis', $userId, $storageKey, $originalName, $mimeType, $byteSize, $checksum);
        $stmt->execute();
        $fileId = $connection->insert_id;
        $stmt->close();

        $updateUser = $connection->prepare('UPDATE users SET avatar_file_id = ? WHERE user_id = ?');
        $updateUser->bind_param('ii', $fileId, $userId);
        $updateUser->execute();
        $updateUser->close();

        $avatarUrlVal = '/uploads/' . $storageKey;
        $prefStmt = $connection->prepare(
            'INSERT INTO user_preferences (user_id, preference_key, preference_value)
             VALUES (?, \'avatar_url\', ?)
             ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)'
        );
        $prefStmt->bind_param('is', $userId, $avatarUrlVal);
        $prefStmt->execute();
        $prefStmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        if (file_exists($targetPath)) {
            @unlink($targetPath);
        }
        throw $exception;
    }

    $fileRecord = fetch_file_record($connection, $fileId);
    json_success('Profile photo updated successfully.', [
        'avatar_file_id' => $fileId,
        'file' => $fileRecord,
        'avatar_url' => '/uploads/' . $storageKey,
    ]);
}

function upload_cv(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $file = $_FILES['cv'] ?? $_FILES['file'] ?? $_FILES['document'] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_error('CV upload failed.', ['cv' => 'Please upload a valid CV document. Only PDF, DOC and DOCX files are supported.'], 422);
    }

    $originalName = (string) ($file['name'] ?? 'cv.pdf');
    $tmpPath = (string) ($file['tmp_name'] ?? '');
    $byteSize = (int) ($file['size'] ?? 0);

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $rejectedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'heic', 'zip', 'exe'];
    $allowedExts = ['pdf', 'doc', 'docx'];

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        json_error('CV upload failed.', ['cv' => 'The uploaded document could not be verified. Please select a PDF, DOC or DOCX file and try again.'], 422);
    }

    if (in_array($ext, $rejectedExts, true) || !in_array($ext, $allowedExts, true)) {
        json_error('CV upload failed.', ['cv' => 'Please upload a valid CV document. Only PDF, DOC and DOCX files are supported.'], 422);
    }

    $mimeType = mime_content_type($tmpPath) ?: 'application/pdf';
    if (str_starts_with(strtolower($mimeType), 'image/')) {
        json_error('CV upload failed.', ['cv' => 'Please upload a valid CV document. Only PDF, DOC and DOCX files are supported.'], 422);
    }

    $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/x-pdf',
        'application/octet-stream'
    ];
    if (!in_array(strtolower($mimeType), $allowedMimes, true) && !in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        json_error('CV upload failed.', ['cv' => 'Please upload a valid CV document. Only PDF, DOC and DOCX files are supported.'], 422);
    }

    $connection = database_connection();
    $userId = $authenticated['user_id'];
    $graduateProfileId = graduate_employment_profile_id($connection, $userId);

    $storageKey = 'cvs/graduate_' . $graduateProfileId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/cvs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $targetPath = $uploadDir . basename($storageKey);

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        json_error('Your CV could not be uploaded due to a server error. Please try again later.', [], 500);
    }

    $checksum = hash_file('sha256', $targetPath) ?: null;

    $connection->begin_transaction();
    try {
        $stmt = $connection->prepare(
            'INSERT INTO files (uploaded_by_user_id, storage_key, original_filename, mime_type, byte_size, checksum_sha256)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssis', $userId, $storageKey, $originalName, $mimeType, $byteSize, $checksum);
        $stmt->execute();
        $fileId = $connection->insert_id;
        $stmt->close();

        $updateGp = $connection->prepare('UPDATE graduate_profiles SET cv_file_id = ? WHERE graduate_profile_id = ?');
        $updateGp->bind_param('ii', $fileId, $graduateProfileId);
        $updateGp->execute();
        $updateGp->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        if (file_exists($targetPath)) {
            @unlink($targetPath);
        }
        throw $exception;
    }

    $fileRecord = fetch_file_record($connection, $fileId);
    json_success('CV uploaded successfully.', [
        'cv_file_id' => $fileId,
        'file' => $fileRecord,
    ]);
}

function delete_cv(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);

    $connection = database_connection();
    $userId = $authenticated['user_id'];
    $graduateProfileId = graduate_employment_profile_id($connection, $userId);

    $stmt = $connection->prepare('SELECT cv_file_id FROM graduate_profiles WHERE graduate_profile_id = ? LIMIT 1');
    $stmt->bind_param('i', $graduateProfileId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $cvFileId = (is_array($profile) && isset($profile['cv_file_id'])) ? (int) $profile['cv_file_id'] : 0;
    if ($cvFileId <= 0) {
        json_error('No CV file is attached to this profile.', [], 404);
    }

    $fileRecord = fetch_file_record($connection, $cvFileId);

    $connection->begin_transaction();
    try {
        $updateGp = $connection->prepare('UPDATE graduate_profiles SET cv_file_id = NULL WHERE graduate_profile_id = ?');
        $updateGp->bind_param('i', $graduateProfileId);
        $updateGp->execute();
        $updateGp->close();

        $delFile = $connection->prepare('DELETE FROM files WHERE file_id = ?');
        $delFile->bind_param('i', $cvFileId);
        $delFile->execute();
        $delFile->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    if (is_array($fileRecord) && !empty($fileRecord['storage_key'])) {
        $filePath = __DIR__ . '/../uploads/' . $fileRecord['storage_key'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    json_success('CV deleted successfully.');
}

function get_user_preferences(): never
{
    $authenticated = require_authenticated_user();
    $userId = (int) $authenticated['user_id'];
    $connection = database_connection();

    $statement = $connection->prepare('SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $result = $statement->get_result();

    $preferences = [];
    while ($row = $result->fetch_assoc()) {
        $preferences[$row['preference_key']] = $row['preference_value'];
    }
    $statement->close();

    json_success('User preferences retrieved successfully.', $preferences);
}

function update_user_preferences(): never
{
    $authenticated = require_authenticated_user();
    $userId = (int) $authenticated['user_id'];
    $input = request_json_body();
    $connection = database_connection();

    $connection->begin_transaction();
    try {
        $statement = $connection->prepare(
            'INSERT INTO user_preferences (user_id, preference_key, preference_value)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)'
        );

        foreach ($input as $key => $value) {
            if (!is_string($key) || mb_strlen($key) > 64) continue;
            if (is_bool($value)) {
                $strValue = $value ? 'true' : 'false';
            } elseif ($value === null) {
                $strValue = '';
            } else {
                $strValue = (string) $value;
            }
            $statement->bind_param('iss', $userId, $key, $strValue);
            $statement->execute();
        }
        $statement->close();
        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    get_user_preferences();
}
