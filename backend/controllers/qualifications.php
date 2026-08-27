<?php

declare(strict_types=1);

function get_graduate_profile_id(mysqli $connection, int $userId): int
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

/** @return array<string, mixed>|null */
function fetch_qualification_by_id(mysqli $connection, int $graduateProfileId, int $qualificationId): ?array
{
    $statement = $connection->prepare(
        'SELECT
            gq.graduate_qualification_id,
            gq.graduate_profile_id,
            gq.programme_id,
            p.name AS programme_name,
            p.code AS programme_code,
            el.education_level_id,
            el.name AS education_level_name,
            el.code AS education_level_code,
            d.department_id,
            d.name AS department_name,
            f.faculty_id,
            f.name AS faculty_name,
            gq.graduation_status_id,
            gs.name AS graduation_status_name,
            gq.degree_classification_id,
            dc.name AS degree_classification_name,
            gq.admission_number,
            gq.registration_number,
            gq.year_of_admission,
            gq.year_of_graduation,
            gq.year_of_graduation AS graduation_year,
            gq.degree_awarded,
            gq.campus,
            gq.campus AS institution,
            gq.created_at,
            gq.updated_at
         FROM graduate_qualifications AS gq
         INNER JOIN programmes AS p ON p.programme_id = gq.programme_id
         INNER JOIN departments AS d ON d.department_id = p.department_id
         INNER JOIN faculties AS f ON f.faculty_id = d.faculty_id
         INNER JOIN education_levels AS el ON el.education_level_id = p.education_level_id
         LEFT JOIN graduation_statuses AS gs ON gs.graduation_status_id = gq.graduation_status_id
         LEFT JOIN degree_classifications AS dc ON dc.degree_classification_id = gq.degree_classification_id
         WHERE gq.graduate_qualification_id = ? AND gq.graduate_profile_id = ?
         LIMIT 1'
    );
    $statement->bind_param('ii', $qualificationId, $graduateProfileId);
    $statement->execute();
    $qualification = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!is_array($qualification)) {
        return null;
    }

    return [
        'graduate_qualification_id' => (int) $qualification['graduate_qualification_id'],
        'graduate_profile_id' => (int) $qualification['graduate_profile_id'],
        'programme_id' => (int) $qualification['programme_id'],
        'programme' => [
            'programme_id' => (int) $qualification['programme_id'],
            'name' => $qualification['programme_name'],
            'code' => $qualification['programme_code'],
            'department' => [
                'department_id' => (int) $qualification['department_id'],
                'name' => $qualification['department_name'],
                'faculty' => [
                    'faculty_id' => (int) $qualification['faculty_id'],
                    'name' => $qualification['faculty_name'],
                ],
            ],
            'education_level' => [
                'education_level_id' => (int) $qualification['education_level_id'],
                'name' => $qualification['education_level_name'],
                'code' => $qualification['education_level_code'],
            ],
        ],
        'education_level' => [
            'education_level_id' => (int) $qualification['education_level_id'],
            'name' => $qualification['education_level_name'],
            'code' => $qualification['education_level_code'],
        ],
        'graduation_status' => $qualification['graduation_status_id'] !== null ? [
            'graduation_status_id' => (int) $qualification['graduation_status_id'],
            'name' => $qualification['graduation_status_name'],
        ] : null,
        'degree_classification' => $qualification['degree_classification_id'] !== null ? [
            'degree_classification_id' => (int) $qualification['degree_classification_id'],
            'name' => $qualification['degree_classification_name'],
        ] : null,
        'admission_number' => $qualification['admission_number'],
        'registration_number' => $qualification['registration_number'],
        'year_of_admission' => $qualification['year_of_admission'] !== null ? (int) $qualification['year_of_admission'] : null,
        'year_of_graduation' => $qualification['year_of_graduation'] !== null ? (int) $qualification['year_of_graduation'] : null,
        'graduation_year' => $qualification['graduation_year'] !== null ? (int) $qualification['graduation_year'] : null,
        'degree_awarded' => $qualification['degree_awarded'],
        'campus' => $qualification['campus'],
        'institution' => $qualification['institution'],
        'programme_name' => $qualification['programme_name'],
        'programme_code' => $qualification['programme_code'],
        'education_level_name' => $qualification['education_level_name'],
        'department_name' => $qualification['department_name'],
        'faculty_name' => $qualification['faculty_name'],
        'graduation_status_name' => $qualification['graduation_status_name'],
        'degree_classification_name' => $qualification['degree_classification_name'],
        'created_at' => $qualification['created_at'],
        'updated_at' => $qualification['updated_at'],
    ];
}

function list_graduate_qualifications(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $graduateProfileId = get_graduate_profile_id($connection, $authenticated['user_id']);

    $statement = $connection->prepare(
        'SELECT
            gq.graduate_qualification_id,
            gq.graduate_profile_id,
            gq.programme_id,
            p.name AS programme_name,
            p.code AS programme_code,
            el.education_level_id,
            el.name AS education_level_name,
            el.code AS education_level_code,
            d.department_id,
            d.name AS department_name,
            f.faculty_id,
            f.name AS faculty_name,
            gq.graduation_status_id,
            gs.name AS graduation_status_name,
            gq.degree_classification_id,
            dc.name AS degree_classification_name,
            gq.admission_number,
            gq.registration_number,
            gq.year_of_admission,
            gq.year_of_graduation,
            gq.year_of_graduation AS graduation_year,
            gq.degree_awarded,
            gq.campus,
            gq.campus AS institution,
            gq.created_at,
            gq.updated_at
         FROM graduate_qualifications AS gq
         INNER JOIN programmes AS p ON p.programme_id = gq.programme_id
         INNER JOIN departments AS d ON d.department_id = p.department_id
         INNER JOIN faculties AS f ON f.faculty_id = d.faculty_id
         INNER JOIN education_levels AS el ON el.education_level_id = p.education_level_id
         LEFT JOIN graduation_statuses AS gs ON gs.graduation_status_id = gq.graduation_status_id
         LEFT JOIN degree_classifications AS dc ON dc.degree_classification_id = gq.degree_classification_id
         WHERE gq.graduate_profile_id = ?
         ORDER BY gq.graduate_qualification_id DESC'
    );
    $statement->bind_param('i', $graduateProfileId);
    $statement->execute();
    $rawList = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $qualifications = [];
    foreach ($rawList as $qualification) {
        $qualifications[] = [
            'graduate_qualification_id' => (int) $qualification['graduate_qualification_id'],
            'graduate_profile_id' => (int) $qualification['graduate_profile_id'],
            'programme_id' => (int) $qualification['programme_id'],
            'programme' => [
                'programme_id' => (int) $qualification['programme_id'],
                'name' => $qualification['programme_name'],
                'code' => $qualification['programme_code'],
                'department' => [
                    'department_id' => (int) $qualification['department_id'],
                    'name' => $qualification['department_name'],
                    'faculty' => [
                        'faculty_id' => (int) $qualification['faculty_id'],
                        'name' => $qualification['faculty_name'],
                    ],
                ],
                'education_level' => [
                    'education_level_id' => (int) $qualification['education_level_id'],
                    'name' => $qualification['education_level_name'],
                    'code' => $qualification['education_level_code'],
                ],
            ],
            'education_level' => [
                'education_level_id' => (int) $qualification['education_level_id'],
                'name' => $qualification['education_level_name'],
                'code' => $qualification['education_level_code'],
            ],
            'graduation_status' => $qualification['graduation_status_id'] !== null ? [
                'graduation_status_id' => (int) $qualification['graduation_status_id'],
                'name' => $qualification['graduation_status_name'],
            ] : null,
            'degree_classification' => $qualification['degree_classification_id'] !== null ? [
                'degree_classification_id' => (int) $qualification['degree_classification_id'],
                'name' => $qualification['degree_classification_name'],
            ] : null,
            'admission_number' => $qualification['admission_number'],
            'registration_number' => $qualification['registration_number'],
            'year_of_admission' => $qualification['year_of_admission'] !== null ? (int) $qualification['year_of_admission'] : null,
            'year_of_graduation' => $qualification['year_of_graduation'] !== null ? (int) $qualification['year_of_graduation'] : null,
            'graduation_year' => $qualification['graduation_year'] !== null ? (int) $qualification['graduation_year'] : null,
            'degree_awarded' => $qualification['degree_awarded'],
            'campus' => $qualification['campus'],
            'institution' => $qualification['institution'],
            'programme_name' => $qualification['programme_name'],
            'programme_code' => $qualification['programme_code'],
            'education_level_name' => $qualification['education_level_name'],
            'department_name' => $qualification['department_name'],
            'faculty_name' => $qualification['faculty_name'],
            'graduation_status_name' => $qualification['graduation_status_name'],
            'degree_classification_name' => $qualification['degree_classification_name'],
            'created_at' => $qualification['created_at'],
            'updated_at' => $qualification['updated_at'],
        ];
    }

    json_success('Graduate qualifications retrieved successfully.', $qualifications);
}

function create_graduate_qualification(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $input = request_json_body();

    $allowed = [
        'programme_id',
        'graduation_status_id',
        'degree_classification_id',
        'admission_number',
        'registration_number',
        'year_of_admission',
        'year_of_graduation',
        'graduation_year',
        'degree_awarded',
        'campus',
        'institution',
    ];
    foreach ($input as $field => $_) {
        if (!in_array($field, $allowed, true)) {
            json_error('Validation failed.', [$field => 'This field is not supported.'], 422);
        }
    }

    $connection = database_connection();
    $graduateProfileId = get_graduate_profile_id($connection, $authenticated['user_id']);

    $programmeId = required_integer($input, 'programme_id', 1, PHP_INT_MAX);
    $statement = $connection->prepare('SELECT programme_id FROM programmes WHERE programme_id = ? AND is_active = 1 LIMIT 1');
    $statement->bind_param('i', $programmeId);
    $statement->execute();
    $prog = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($prog)) {
        json_error('Validation failed.', ['programme_id' => 'Select a valid programme.'], 422);
    }

    $statusId = null;
    if (array_key_exists('graduation_status_id', $input) && $input['graduation_status_id'] !== null) {
        $statusId = required_integer($input, 'graduation_status_id', 1, PHP_INT_MAX);
        $statement = $connection->prepare('SELECT graduation_status_id FROM graduation_statuses WHERE graduation_status_id = ? AND is_active = 1 LIMIT 1');
        $statement->bind_param('i', $statusId);
        $statement->execute();
        $status = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($status)) {
            json_error('Validation failed.', ['graduation_status_id' => 'Select a valid graduation status.'], 422);
        }
    }

    $classificationId = null;
    if (array_key_exists('degree_classification_id', $input) && $input['degree_classification_id'] !== null) {
        $classificationId = required_integer($input, 'degree_classification_id', 1, PHP_INT_MAX);
        $statement = $connection->prepare('SELECT degree_classification_id FROM degree_classifications WHERE degree_classification_id = ? AND is_active = 1 LIMIT 1');
        $statement->bind_param('i', $classificationId);
        $statement->execute();
        $class = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($class)) {
            json_error('Validation failed.', ['degree_classification_id' => 'Select a valid degree classification.'], 422);
        }
    }

    $admissionNo = null;
    if (array_key_exists('admission_number', $input) && $input['admission_number'] !== null) {
        if (!is_string($input['admission_number'])) json_error('Validation failed.', ['admission_number' => 'Admission number must be a string.'], 422);
        $admissionNo = trim($input['admission_number']);
        if ($admissionNo === '') $admissionNo = null;
        elseif (mb_strlen($admissionNo) > 100) json_error('Validation failed.', ['admission_number' => 'Admission number cannot exceed 100 characters.'], 422);
    }

    $regNo = null;
    if (array_key_exists('registration_number', $input) && $input['registration_number'] !== null) {
        if (!is_string($input['registration_number'])) json_error('Validation failed.', ['registration_number' => 'Registration number must be a string.'], 422);
        $regNo = trim($input['registration_number']);
        if ($regNo === '') $regNo = null;
        elseif (mb_strlen($regNo) > 100) json_error('Validation failed.', ['registration_number' => 'Registration number cannot exceed 100 characters.'], 422);
    }

    $admissionYear = null;
    if (array_key_exists('year_of_admission', $input) && $input['year_of_admission'] !== null) {
        $admissionYear = required_integer($input, 'year_of_admission', 1950, 2100);
    }

    $graduationYear = null;
    if (array_key_exists('year_of_graduation', $input) && $input['year_of_graduation'] !== null) {
        $graduationYear = required_integer($input, 'year_of_graduation', 1950, 2100);
    } elseif (array_key_exists('graduation_year', $input) && $input['graduation_year'] !== null) {
        $graduationYear = required_integer($input, 'graduation_year', 1950, 2100);
    }

    $degreeAwarded = null;
    if (array_key_exists('degree_awarded', $input) && $input['degree_awarded'] !== null) {
        if (!is_string($input['degree_awarded'])) json_error('Validation failed.', ['degree_awarded' => 'Degree awarded must be a string.'], 422);
        $degreeAwarded = trim($input['degree_awarded']);
        if ($degreeAwarded === '') $degreeAwarded = null;
        elseif (mb_strlen($degreeAwarded) > 250) json_error('Validation failed.', ['degree_awarded' => 'Degree awarded cannot exceed 250 characters.'], 422);
    }

    $campus = null;
    if (array_key_exists('campus', $input) && $input['campus'] !== null) {
        if (!is_string($input['campus'])) json_error('Validation failed.', ['campus' => 'Campus must be a string.'], 422);
        $campus = trim($input['campus']);
        if ($campus === '') $campus = null;
        elseif (mb_strlen($campus) > 150) json_error('Validation failed.', ['campus' => 'Campus cannot exceed 150 characters.'], 422);
    } elseif (array_key_exists('institution', $input) && $input['institution'] !== null) {
        if (!is_string($input['institution'])) json_error('Validation failed.', ['institution' => 'Institution must be a string.'], 422);
        $campus = trim($input['institution']);
        if ($campus === '') $campus = null;
        elseif (mb_strlen($campus) > 150) json_error('Validation failed.', ['institution' => 'Institution cannot exceed 150 characters.'], 422);
    }

    try {
        $connection->begin_transaction();

        $statement = $connection->prepare(
            'INSERT INTO graduate_qualifications
                (graduate_profile_id, programme_id, graduation_status_id, degree_classification_id, admission_number, registration_number, year_of_admission, year_of_graduation, degree_awarded, campus)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param(
            'iiiiississ',
            $graduateProfileId,
            $programmeId,
            $statusId,
            $classificationId,
            $admissionNo,
            $regNo,
            $admissionYear,
            $graduationYear,
            $degreeAwarded,
            $campus
        );
        $statement->execute();
        $qualificationId = $connection->insert_id;
        $statement->close();

        $connection->commit();
    } catch (mysqli_sql_exception $exception) {
        rollback_transaction($connection);
        if ($exception->getCode() === 1062) {
            json_error('A qualification with that unique admission or registration number already exists.', [], 409);
        }
        throw $exception;
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $createdRecord = fetch_qualification_by_id($connection, $graduateProfileId, $qualificationId);
    json_success('Graduate qualification created successfully.', $createdRecord, 201);
}

function update_graduate_qualification(int $qualificationId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $graduateProfileId = get_graduate_profile_id($connection, $authenticated['user_id']);

    $existing = fetch_qualification_by_id($connection, $graduateProfileId, $qualificationId);
    if ($existing === null) {
        json_error('Graduate qualification was not found.', [], 404);
    }

    $input = request_json_body();
    $allowed = [
        'programme_id',
        'graduation_status_id',
        'degree_classification_id',
        'admission_number',
        'registration_number',
        'year_of_admission',
        'year_of_graduation',
        'graduation_year',
        'degree_awarded',
        'campus',
        'institution',
    ];
    foreach ($input as $field => $_) {
        if (!in_array($field, $allowed, true)) {
            json_error('Validation failed.', [$field => 'This field is not supported.'], 422);
        }
    }

    if (empty($input)) {
        json_error('Validation failed.', ['request' => 'Provide at least one field to update.'], 422);
    }

    $hasProg = array_key_exists('programme_id', $input) ? 1 : 0;
    $progVal = 0;
    if ($hasProg) {
        $progVal = required_integer($input, 'programme_id', 1, PHP_INT_MAX);
        $statement = $connection->prepare('SELECT programme_id FROM programmes WHERE programme_id = ? AND is_active = 1 LIMIT 1');
        $statement->bind_param('i', $progVal);
        $statement->execute();
        $prog = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($prog)) {
            json_error('Validation failed.', ['programme_id' => 'Select a valid programme.'], 422);
        }
    }

    $hasStatus = array_key_exists('graduation_status_id', $input) ? 1 : 0;
    $statusVal = null;
    if ($hasStatus && $input['graduation_status_id'] !== null) {
        $statusVal = required_integer($input, 'graduation_status_id', 1, PHP_INT_MAX);
        $statement = $connection->prepare('SELECT graduation_status_id FROM graduation_statuses WHERE graduation_status_id = ? AND is_active = 1 LIMIT 1');
        $statement->bind_param('i', $statusVal);
        $statement->execute();
        $status = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($status)) {
            json_error('Validation failed.', ['graduation_status_id' => 'Select a valid graduation status.'], 422);
        }
    }

    $hasClass = array_key_exists('degree_classification_id', $input) ? 1 : 0;
    $classVal = null;
    if ($hasClass && $input['degree_classification_id'] !== null) {
        $classVal = required_integer($input, 'degree_classification_id', 1, PHP_INT_MAX);
        $statement = $connection->prepare('SELECT degree_classification_id FROM degree_classifications WHERE degree_classification_id = ? AND is_active = 1 LIMIT 1');
        $statement->bind_param('i', $classVal);
        $statement->execute();
        $class = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($class)) {
            json_error('Validation failed.', ['degree_classification_id' => 'Select a valid degree classification.'], 422);
        }
    }

    $hasAdmNo = array_key_exists('admission_number', $input) ? 1 : 0;
    $admNoVal = null;
    if ($hasAdmNo && $input['admission_number'] !== null) {
        if (!is_string($input['admission_number'])) json_error('Validation failed.', ['admission_number' => 'Admission number must be a string.'], 422);
        $admNoVal = trim($input['admission_number']);
        if ($admNoVal === '') $admNoVal = null;
        elseif (mb_strlen($admNoVal) > 100) json_error('Validation failed.', ['admission_number' => 'Admission number cannot exceed 100 characters.'], 422);
    }

    $hasRegNo = array_key_exists('registration_number', $input) ? 1 : 0;
    $regNoVal = null;
    if ($hasRegNo && $input['registration_number'] !== null) {
        if (!is_string($input['registration_number'])) json_error('Validation failed.', ['registration_number' => 'Registration number must be a string.'], 422);
        $regNoVal = trim($input['registration_number']);
        if ($regNoVal === '') $regNoVal = null;
        elseif (mb_strlen($regNoVal) > 100) json_error('Validation failed.', ['registration_number' => 'Registration number cannot exceed 100 characters.'], 422);
    }

    $hasAdmYr = array_key_exists('year_of_admission', $input) ? 1 : 0;
    $admYrVal = null;
    if ($hasAdmYr && $input['year_of_admission'] !== null) {
        $admYrVal = required_integer($input, 'year_of_admission', 1950, 2100);
    }

    $hasGradYr = (array_key_exists('year_of_graduation', $input) || array_key_exists('graduation_year', $input)) ? 1 : 0;
    $gradYrVal = null;
    if ($hasGradYr) {
        if (array_key_exists('year_of_graduation', $input) && $input['year_of_graduation'] !== null) {
            $gradYrVal = required_integer($input, 'year_of_graduation', 1950, 2100);
        } elseif (array_key_exists('graduation_year', $input) && $input['graduation_year'] !== null) {
            $gradYrVal = required_integer($input, 'graduation_year', 1950, 2100);
        }
    }

    $hasDegree = array_key_exists('degree_awarded', $input) ? 1 : 0;
    $degreeVal = null;
    if ($hasDegree && $input['degree_awarded'] !== null) {
        if (!is_string($input['degree_awarded'])) json_error('Validation failed.', ['degree_awarded' => 'Degree awarded must be a string.'], 422);
        $degreeVal = trim($input['degree_awarded']);
        if ($degreeVal === '') $degreeVal = null;
        elseif (mb_strlen($degreeVal) > 250) json_error('Validation failed.', ['degree_awarded' => 'Degree awarded cannot exceed 250 characters.'], 422);
    }

    $hasCampus = (array_key_exists('campus', $input) || array_key_exists('institution', $input)) ? 1 : 0;
    $campusVal = null;
    if ($hasCampus) {
        if (array_key_exists('campus', $input) && $input['campus'] !== null) {
            if (!is_string($input['campus'])) json_error('Validation failed.', ['campus' => 'Campus must be a string.'], 422);
            $campusVal = trim($input['campus']);
            if ($campusVal === '') $campusVal = null;
            elseif (mb_strlen($campusVal) > 150) json_error('Validation failed.', ['campus' => 'Campus cannot exceed 150 characters.'], 422);
        } elseif (array_key_exists('institution', $input) && $input['institution'] !== null) {
            if (!is_string($input['institution'])) json_error('Validation failed.', ['institution' => 'Institution must be a string.'], 422);
            $campusVal = trim($input['institution']);
            if ($campusVal === '') $campusVal = null;
            elseif (mb_strlen($campusVal) > 150) json_error('Validation failed.', ['institution' => 'Institution cannot exceed 150 characters.'], 422);
        }
    }

    try {
        $connection->begin_transaction();

        $statement = $connection->prepare(
            'UPDATE graduate_qualifications SET
                programme_id = CASE WHEN ? THEN ? ELSE programme_id END,
                graduation_status_id = CASE WHEN ? THEN ? ELSE graduation_status_id END,
                degree_classification_id = CASE WHEN ? THEN ? ELSE degree_classification_id END,
                admission_number = CASE WHEN ? THEN ? ELSE admission_number END,
                registration_number = CASE WHEN ? THEN ? ELSE registration_number END,
                year_of_admission = CASE WHEN ? THEN ? ELSE year_of_admission END,
                year_of_graduation = CASE WHEN ? THEN ? ELSE year_of_graduation END,
                degree_awarded = CASE WHEN ? THEN ? ELSE degree_awarded END,
                campus = CASE WHEN ? THEN ? ELSE campus END
             WHERE graduate_qualification_id = ? AND graduate_profile_id = ?'
        );

        $statement->bind_param(
            'iiiiiiisisiiiiisisii',
            $hasProg, $progVal,
            $hasStatus, $statusVal,
            $hasClass, $classVal,
            $hasAdmNo, $admNoVal,
            $hasRegNo, $regNoVal,
            $hasAdmYr, $admYrVal,
            $hasGradYr, $gradYrVal,
            $hasDegree, $degreeVal,
            $hasCampus, $campusVal,
            $qualificationId, $graduateProfileId
        );
        $statement->execute();
        $statement->close();

        $connection->commit();
    } catch (mysqli_sql_exception $exception) {
        rollback_transaction($connection);
        if ($exception->getCode() === 1062) {
            json_error('A qualification with that unique admission or registration number already exists.', [], 409);
        }
        throw $exception;
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $updatedRecord = fetch_qualification_by_id($connection, $graduateProfileId, $qualificationId);
    json_success('Graduate qualification updated successfully.', $updatedRecord);
}

function delete_graduate_qualification(int $qualificationId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $graduateProfileId = get_graduate_profile_id($connection, $authenticated['user_id']);

    $existing = fetch_qualification_by_id($connection, $graduateProfileId, $qualificationId);
    if ($existing === null) {
        json_error('Graduate qualification was not found.', [], 404);
    }

    try {
        $connection->begin_transaction();
        $statement = $connection->prepare(
            'DELETE FROM graduate_qualifications WHERE graduate_qualification_id = ? AND graduate_profile_id = ?'
        );
        $statement->bind_param('ii', $qualificationId, $graduateProfileId);
        $statement->execute();
        $statement->close();
        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Graduate qualification deleted successfully.');
}
