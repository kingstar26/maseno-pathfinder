<?php

declare(strict_types=1);

function list_administrator_users(): never
{
    require_administrator(); $connection = database_connection();
    $statement = $connection->prepare('SELECT u.user_id, CONCAT(u.first_name, \' \', u.last_name) AS name, u.email, r.role_id, r.code AS role, r.name AS role_name, s.account_status_id, s.code AS account_status, s.name AS account_status_name, u.created_at, u.last_login_at FROM users u INNER JOIN roles r ON r.role_id = u.role_id INNER JOIN account_statuses s ON s.account_status_id = u.account_status_id ORDER BY u.created_at DESC');
    $statement->execute(); $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC); $statement->close();
    json_success('Users retrieved successfully.', $rows);
}
function get_administrator_user(int $userId): never
{
    require_administrator(); $connection = database_connection();
    $statement = $connection->prepare('SELECT u.user_id, u.first_name, u.last_name, u.email, u.last_login_at, u.email_verified_at, u.created_at, u.updated_at, r.role_id, r.code AS role, r.name AS role_name, s.account_status_id, s.code AS account_status, s.name AS account_status_name FROM users u INNER JOIN roles r ON r.role_id = u.role_id INNER JOIN account_statuses s ON s.account_status_id = u.account_status_id WHERE u.user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId); $statement->execute(); $row = $statement->get_result()->fetch_assoc(); $statement->close();
    if (!is_array($row)) json_error('User not found.', [], 404); json_success('User retrieved successfully.', $row);
}
function administrator_target_status_id(mysqli $connection, array $input): int
{
    if (isset($input['account_status_id'])) { $id = required_integer($input, 'account_status_id', 1, PHP_INT_MAX); $stmt = $connection->prepare('SELECT account_status_id FROM account_statuses WHERE account_status_id = ? AND is_active = 1'); $stmt->bind_param('i', $id); }
    else { $code = required_string($input, 'account_status', 30); $stmt = $connection->prepare('SELECT account_status_id FROM account_statuses WHERE code = ? AND is_active = 1'); $stmt->bind_param('s', $code); }
    $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close(); if (!is_array($row)) json_error('Account status not found.', [], 422); return (int) $row['account_status_id'];
}
function update_administrator_user_status(int $userId): never
{
    require_administrator(); $connection = database_connection(); $statusId = administrator_target_status_id($connection, request_json_body());
    $statement = $connection->prepare('UPDATE users SET account_status_id = ? WHERE user_id = ?'); $statement->bind_param('ii', $statusId, $userId); $statement->execute(); $affected = $statement->affected_rows; $statement->close();
    if ($affected === 0) json_error('User not found or status is unchanged.', [], 404);
    $revoke = $connection->prepare('UPDATE user_sessions SET revoked_at = NOW(6) WHERE user_id = ? AND revoked_at IS NULL'); $revoke->bind_param('i', $userId); $revoke->execute(); $revoke->close();

    $codeStmt = $connection->prepare('SELECT code, name FROM account_statuses WHERE account_status_id = ? LIMIT 1');
    $codeStmt->bind_param('i', $statusId);
    $codeStmt->execute();
    $statusRow = $codeStmt->get_result()->fetch_assoc();
    $codeStmt->close();

    $statusCode = is_array($statusRow) ? ($statusRow['code'] ?? '') : '';
    if ($statusCode === 'active') {
        send_mugts_notification_email($userId, 'Account Reactivated', 'Account Reactivated', 'Your Maseno University Graduate Tracer System account has been activated/reactivated.', '/login');
    } elseif ($statusCode === 'suspended' || $statusCode === 'inactive') {
        send_mugts_notification_email($userId, 'Account Suspended', 'Account Suspended', 'Your Maseno University Graduate Tracer System account status has been updated to suspended/inactive. Please contact system support.', null);
    }

    json_success('User account status updated successfully.');
}
function update_administrator_user_role(int $userId): never
{
    require_administrator(); $connection = database_connection(); $input = request_json_body();
    if (isset($input['role_id'])) { $roleId = required_integer($input, 'role_id', 1, PHP_INT_MAX); $roleStmt = $connection->prepare('SELECT role_id, code FROM roles WHERE role_id = ?'); $roleStmt->bind_param('i', $roleId); }
    else { $code = required_string($input, 'role', 30); $roleStmt = $connection->prepare('SELECT role_id, code FROM roles WHERE code = ?'); $roleStmt->bind_param('s', $code); }
    $roleStmt->execute(); $role = $roleStmt->get_result()->fetch_assoc(); $roleStmt->close(); if (!is_array($role)) json_error('Role not found.', [], 422);
    $currentStmt = $connection->prepare('SELECT r.code FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ? LIMIT 1'); $currentStmt->bind_param('i', $userId); $currentStmt->execute(); $current = $currentStmt->get_result()->fetch_assoc(); $currentStmt->close(); if (!is_array($current)) json_error('User not found.', [], 404);
    if ($current['code'] === 'administrator' && $role['code'] !== 'administrator' && administrator_count($connection, "SELECT COUNT(*) AS total FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE r.code = 'administrator'") <= 1) json_error('The last administrator account cannot be reassigned.', [], 409);
    $connection->begin_transaction();
    try { $update = $connection->prepare('UPDATE users SET role_id = ? WHERE user_id = ?'); $newRoleId = (int) $role['role_id']; $update->bind_param('ii', $newRoleId, $userId); $update->execute(); $update->close(); if ($role['code'] === 'administrator') { $profile = $connection->prepare('INSERT INTO administrator_profiles (user_id) SELECT ? WHERE NOT EXISTS (SELECT 1 FROM administrator_profiles WHERE user_id = ?)'); $profile->bind_param('ii', $userId, $userId); $profile->execute(); $profile->close(); } $connection->commit(); } catch (Throwable $exception) { $connection->rollback(); throw $exception; }
    json_success('User role updated successfully.');
}
