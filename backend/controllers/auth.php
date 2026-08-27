<?php

declare(strict_types=1);

const AUTH_SESSION_TTL_SECONDS = 86400;

/** @return array<string, mixed> */
function resolve_user_avatar_url(mysqli $connection, int $userId, ?int $avatarFileId = null): ?string
{
    $effectiveAvatarFileId = $avatarFileId;

    if ($effectiveAvatarFileId === null || $effectiveAvatarFileId <= 0) {
        $userStmt = $connection->prepare('SELECT avatar_file_id FROM users WHERE user_id = ? LIMIT 1');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userRow = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if (is_array($userRow) && $userRow['avatar_file_id'] !== null) {
            $effectiveAvatarFileId = (int) $userRow['avatar_file_id'];
        }
    }

    if ($effectiveAvatarFileId !== null && $effectiveAvatarFileId > 0) {
        $fileStmt = $connection->prepare('SELECT storage_key FROM files WHERE file_id = ? LIMIT 1');
        $fileStmt->bind_param('i', $effectiveAvatarFileId);
        $fileStmt->execute();
        $fileRow = $fileStmt->get_result()->fetch_assoc();
        $fileStmt->close();

        if (is_array($fileRow) && !empty($fileRow['storage_key'])) {
            $storageKey = ltrim((string) $fileRow['storage_key'], '/\\');
            $uploadsRoot = realpath(__DIR__ . '/../uploads');
            $avatarPath = $uploadsRoot !== false ? $uploadsRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storageKey) : false;

            if (
                $uploadsRoot !== false
                && $avatarPath !== false
                && str_starts_with(strtolower($avatarPath), strtolower($uploadsRoot . DIRECTORY_SEPARATOR))
                && is_file($avatarPath)
            ) {
                return '/uploads/' . $storageKey;
            }
        }
    }

    $prefStmt = $connection->prepare('SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = \'avatar_url\' LIMIT 1');
    $prefStmt->bind_param('i', $userId);
    $prefStmt->execute();
    $prefRow = $prefStmt->get_result()->fetch_assoc();
    $prefStmt->close();

    if (is_array($prefRow) && !empty($prefRow['preference_value'])) {
        $prefUrl = trim((string) $prefRow['preference_value']);
        if ($prefUrl !== '') {
            return $prefUrl;
        }
    }

    return null;
}

/** @return array<string, mixed> */
function authentication_user_payload(array $user, ?string $programme = null, ?int $graduationYear = null): array
{
    return [
        'id' => (string) $user['user_id'],
        'first_name' => (string) $user['first_name'],
        'last_name' => (string) $user['last_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        'avatar_url' => $user['avatar_url'] ?? null,
        'last_login_at' => $user['last_login_at'] === null ? null : (string) $user['last_login_at'],
        'programme' => $programme,
        'graduation_year' => $graduationYear,
    ];
}

function issue_user_session(mysqli $connection, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $hash = session_token_hash($token);
    $expiresAt = gmdate('Y-m-d H:i:s', time() + AUTH_SESSION_TTL_SECONDS);
    $rememberMe = 0;
    $statement = $connection->prepare('INSERT INTO user_sessions (user_id, token_hash, remember_me, expires_at) VALUES (?, ?, ?, ?)');
    $statement->bind_param('isis', $userId, $hash, $rememberMe, $expiresAt);
    $statement->execute();
    $statement->close();
    return $token;
}

function rollback_transaction(mysqli $connection): void
{
    try {
        $connection->rollback();
    } catch (Throwable) {
    }
}

function register_graduate(): never
{
    $input = request_json_body();
    $firstName = required_string($input, 'first_name', 100);
    $lastName = required_string($input, 'last_name', 100);
    $email = validated_email(required_string($input, 'email', 254));
    $passwordHash = password_hash(validated_password(required_string($input, 'password', 255)), PASSWORD_DEFAULT);
    $programmeId = required_integer($input, 'programme', 1, PHP_INT_MAX);
    $graduationYear = required_integer($input, 'graduation_year', 1990, (int) gmdate('Y') + 1);
    $connection = database_connection();

    try {
        $connection->begin_transaction();
        $statement = $connection->prepare('SELECT programme_id, name FROM programmes WHERE programme_id = ? AND is_active = 1 LIMIT 1');
        $statement->bind_param('i', $programmeId);
        $statement->execute();
        $programme = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($programme)) {
            rollback_transaction($connection);
            json_error('Validation failed.', ['programme' => 'Select a valid programme.'], 422);
        }

        $graduateRole = 'graduate';
        $statement = $connection->prepare('SELECT role_id FROM roles WHERE code = ? LIMIT 1');
        $statement->bind_param('s', $graduateRole);
        $statement->execute();
        $role = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($role)) {
            throw new RuntimeException('The graduate role is unavailable.');
        }

        $activeStatus = 'active';
        $statement = $connection->prepare('SELECT account_status_id FROM account_statuses WHERE code = ? AND is_active = 1 LIMIT 1');
        $statement->bind_param('s', $activeStatus);
        $statement->execute();
        $status = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($status)) {
            throw new RuntimeException('The active account status is unavailable.');
        }

        $roleId = (int) $role['role_id'];
        $statusId = (int) $status['account_status_id'];
        $statement = $connection->prepare('INSERT INTO users (role_id, account_status_id, first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->bind_param('iissss', $roleId, $statusId, $firstName, $lastName, $email, $passwordHash);
        $statement->execute();
        $userId = $connection->insert_id;
        $statement->close();

        $statement = $connection->prepare('INSERT INTO person_profiles (user_id) VALUES (?)');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $statement->close();
        $statement = $connection->prepare('INSERT INTO graduate_profiles (user_id) VALUES (?)');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $graduateProfileId = $connection->insert_id;
        $statement->close();
        $statement = $connection->prepare('INSERT INTO graduate_qualifications (graduate_profile_id, programme_id, year_of_graduation) VALUES (?, ?, ?)');
        $statement->bind_param('iii', $graduateProfileId, $programmeId, $graduationYear);
        $statement->execute();
        $statement->close();
        $token = issue_user_session($connection, $userId);
        $connection->commit();
    } catch (mysqli_sql_exception $exception) {
        rollback_transaction($connection);
        if ($exception->getCode() === 1062) {
            json_error('An account with this email address already exists.', ['email' => 'Email address is already registered.'], 409);
        }
        throw $exception;
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_auth_success(['user' => authentication_user_payload(['user_id' => $userId, 'first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'role' => $graduateRole, 'last_login_at' => null], (string) $programme['name'], $graduationYear), 'token' => $token], 201);
}

function login_user(): never
{
    $input = request_json_body();
    $email = validated_email(required_string($input, 'email', 254));
    $password = required_string($input, 'password', 255);
    $connection = database_connection();
    $statement = $connection->prepare('SELECT u.user_id, u.first_name, u.last_name, u.email, u.password_hash, u.last_login_at, u.avatar_file_id, r.code AS role, a.code AS account_status FROM users AS u INNER JOIN roles AS r ON r.role_id = u.role_id INNER JOIN account_statuses AS a ON a.account_status_id = u.account_status_id WHERE u.email = ? LIMIT 1');
    $statement->bind_param('s', $email);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($user) || !password_verify($password, (string) $user['password_hash'])) {
        json_error('Incorrect email address or password.', [], 401);
    }
    if ((string) $user['account_status'] !== 'active') {
        json_error('This account is not active.', [], 403);
    }

    try {
        $connection->begin_transaction();
        $userId = (int) $user['user_id'];
        $statement = $connection->prepare('UPDATE users SET last_login_at = NOW(6) WHERE user_id = ?');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $statement->close();
        $token = issue_user_session($connection, $userId);
        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $statement = $connection->prepare('SELECT last_login_at FROM users WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $loginTimestamp = $statement->get_result()->fetch_assoc();
    $statement->close();
    $user['last_login_at'] = is_array($loginTimestamp) ? $loginTimestamp['last_login_at'] : null;

    $user['avatar_url'] = resolve_user_avatar_url($connection, $userId, $user['avatar_file_id'] !== null ? (int) $user['avatar_file_id'] : null);

    $programmeName = null;
    $graduationYear = null;
    if ((string) $user['role'] === 'graduate') {
        $qualStmt = $connection->prepare(
            'SELECT p.name AS programme_name, gq.year_of_graduation
             FROM graduate_qualifications AS gq
             INNER JOIN graduate_profiles AS gp ON gp.graduate_profile_id = gq.graduate_profile_id
             INNER JOIN programmes AS p ON p.programme_id = gq.programme_id
             WHERE gp.user_id = ? ORDER BY gq.graduate_qualification_id DESC LIMIT 1'
        );
        $qualStmt->bind_param('i', $userId);
        $qualStmt->execute();
        $qualRow = $qualStmt->get_result()->fetch_assoc();
        $qualStmt->close();
        if (is_array($qualRow)) {
            $programmeName = $qualRow['programme_name'];
            $graduationYear = $qualRow['year_of_graduation'] !== null ? (int) $qualRow['year_of_graduation'] : null;
        }
    }

    json_auth_success(['user' => authentication_user_payload($user, $programmeName, $graduationYear), 'token' => $token]);
}

function change_user_password(): never
{
    $authenticatedUser = require_authenticated_user();
    $input = request_json_body();
    $currentPassword = required_string($input, 'current_password', 255);
    $newPassword = validated_password(required_string($input, 'new_password', 255), 'new_password');
    $connection = database_connection();
    $userId = $authenticatedUser['user_id'];

    try {
        $connection->begin_transaction();
        $statement = $connection->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($user) || !password_verify($currentPassword, (string) $user['password_hash'])) {
            rollback_transaction($connection);
            json_error('The current password is incorrect.', [], 401);
        }
        if ($currentPassword === $newPassword || password_verify($newPassword, (string) $user['password_hash'])) {
            rollback_transaction($connection);
            json_error('Your new password must be different from your current password.', ['new_password' => 'Your new password must be different from your current password.'], 422);
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $statement = $connection->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $statement->bind_param('si', $hash, $userId);
        $statement->execute();
        $statement->close();
        $statement = $connection->prepare('INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)');
        $statement->bind_param('is', $userId, $hash);
        $statement->execute();
        $statement->close();
        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    send_mugts_notification_email(
        (int) $userId,
        'Security Notification: Password Changed',
        'Your MUGTS Account Password Has Been Changed',
        '<p>Your password for the Maseno University Graduate Tracer System was updated on ' . date('M j, Y, g:i A') . '.</p><p>If you did not perform this action, please contact Maseno University support immediately.</p>'
    );

    json_success('Password changed successfully.');
}
