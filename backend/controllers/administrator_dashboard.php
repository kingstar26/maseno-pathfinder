<?php

declare(strict_types=1);

/** @return array{user_id:int, role:string, session_id:int, administrator_profile_id:int} */
function require_administrator(): array
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['administrator']);

    $connection = database_connection();
    $statement = $connection->prepare('SELECT administrator_profile_id FROM administrator_profiles WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $authenticated['user_id']);
    $statement->execute();
    $profile = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!is_array($profile) || !isset($profile['administrator_profile_id'])) {
        $instStmt = $connection->prepare('INSERT INTO administrator_profiles (user_id) VALUES (?)');
        $instStmt->bind_param('i', $authenticated['user_id']);
        $instStmt->execute();
        $adminProfileId = $connection->insert_id;
        $instStmt->close();
        $authenticated['administrator_profile_id'] = (int) $adminProfileId;
    } else {
        $authenticated['administrator_profile_id'] = (int) $profile['administrator_profile_id'];
    }
    return $authenticated;
}

function administrator_count(mysqli $connection, string $sql, string $types = '', array $values = []): int
{
    $statement = $connection->prepare($sql);
    if ($types === 'i') {
        $value = (int) $values[0];
        $statement->bind_param('i', $value);
    }
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();
    return is_array($row) ? (int) $row['total'] : 0;
}

function get_administrator_dashboard(): never
{
    $administrator = require_administrator();
    $connection = database_connection();
    $counts = [
        'total_users' => administrator_count($connection, 'SELECT COUNT(*) AS total FROM users'),
        'total_graduates' => administrator_count($connection, "SELECT COUNT(*) AS total FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE r.code = 'graduate'"),
        'total_management_users' => administrator_count($connection, "SELECT COUNT(*) AS total FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE r.code = 'management'"),
        'total_administrators' => administrator_count($connection, "SELECT COUNT(*) AS total FROM users u INNER JOIN roles r ON r.role_id = u.role_id WHERE r.code = 'administrator'"),
        'active_accounts' => administrator_count($connection, "SELECT COUNT(*) AS total FROM users u INNER JOIN account_statuses s ON s.account_status_id = u.account_status_id WHERE s.code = 'active'"),
        'inactive_accounts' => administrator_count($connection, "SELECT COUNT(*) AS total FROM users u INNER JOIN account_statuses s ON s.account_status_id = u.account_status_id WHERE s.code = 'inactive'"),
        'suspended_accounts' => administrator_count($connection, "SELECT COUNT(*) AS total FROM users u INNER JOIN account_statuses s ON s.account_status_id = u.account_status_id WHERE s.code = 'suspended'"),
        'total_faculties' => administrator_count($connection, 'SELECT COUNT(*) AS total FROM faculties'),
        'total_departments' => administrator_count($connection, 'SELECT COUNT(*) AS total FROM departments'),
        'total_programmes' => administrator_count($connection, 'SELECT COUNT(*) AS total FROM programmes'),
    ];
    $activity = [];
    foreach ([
        ['Users', 'SELECT CONCAT(first_name, \' \', last_name) AS label, created_at FROM users ORDER BY created_at DESC LIMIT 5'],
        ['Faculties', 'SELECT name AS label, created_at FROM faculties ORDER BY created_at DESC LIMIT 5'],
        ['Departments', 'SELECT name AS label, created_at FROM departments ORDER BY created_at DESC LIMIT 5'],
        ['Programmes', 'SELECT name AS label, created_at FROM programmes ORDER BY created_at DESC LIMIT 5'],
    ] as [$type, $sql]) {
        $statement = $connection->prepare($sql); $statement->execute();
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $activity[] = ['type' => $type, 'label' => $row['label'], 'created_at' => $row['created_at']];
        $statement->close();
    }
    usort($activity, static fn(array $a, array $b): int => strcmp((string) $b['created_at'], (string) $a['created_at']));
    json_success('Administrator dashboard retrieved successfully.', [...$counts, 'administrator_profile_id' => $administrator['administrator_profile_id'], 'recent_system_activity' => array_slice($activity, 0, 10)]);
}
