<?php

declare(strict_types=1);

/**
 * Resolves the bearer token against the existing user_sessions table.
 * It is intentionally not attached to a route until protected endpoints exist.
 *
 * @return array{user_id: int, role: string, session_id: int}
 */
function require_authenticated_user(): array
{
    $token = bearer_token();
    if ($token === null) {
        json_error('Authentication is required.', [], 401);
    }

    $connection = database_connection();
    $tokenHash = session_token_hash($token);
    $statement = $connection->prepare(
        'SELECT s.session_id, s.user_id, r.code AS role
         FROM user_sessions AS s
         INNER JOIN users AS u ON u.user_id = s.user_id
         INNER JOIN roles AS r ON r.role_id = u.role_id
         INNER JOIN account_statuses AS a ON a.account_status_id = u.account_status_id
         WHERE s.token_hash = ?
           AND a.code = \'active\'
           AND s.revoked_at IS NULL
           AND s.expires_at > NOW(6)
         LIMIT 1'
    );
    $statement->bind_param('s', $tokenHash);
    $statement->execute();
    $result = $statement->get_result();
    $session = $result->fetch_assoc();
    $statement->close();

    if (!is_array($session)) {
        json_error('Your session expired due to 5 minutes of inactivity. Please sign in again.', [], 401);
    }

    $sessionId = (int) $session['session_id'];
    $lastUsedStatement = $connection->prepare(
        'UPDATE user_sessions SET last_used_at = NOW(6) WHERE session_id = ?'
    );
    $lastUsedStatement->bind_param('i', $sessionId);
    $lastUsedStatement->execute();
    $lastUsedStatement->close();

    return [
        'user_id' => (int) $session['user_id'],
        'role' => (string) $session['role'],
        'session_id' => $sessionId,
    ];
}

/** @param array<int, string> $allowedRoles */
function require_role(array $authenticatedUser, array $allowedRoles): void
{
    if (!in_array($authenticatedUser['role'], $allowedRoles, true)) {
        json_error('You do not have permission to perform this action.', [], 403);
    }
}
