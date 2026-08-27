<?php

declare(strict_types=1);

function list_graduate_notifications(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $userId = $authenticated['user_id'];

    $statement = $connection->prepare(
        'SELECT notification_id, user_id, title, message, notification_type, is_read, read_at, created_at, updated_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC, notification_id DESC'
    );
    $statement->bind_param('i', $userId);
    $statement->execute();
    $rawList = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $notifications = [];
    foreach ($rawList as $row) {
        $notifications[] = [
            'notification_id' => (int) $row['notification_id'],
            'user_id' => (int) $row['user_id'],
            'title' => (string) $row['title'],
            'message' => (string) $row['message'],
            'notification_type' => $row['notification_type'] !== null ? (string) $row['notification_type'] : null,
            'is_read' => (int) $row['is_read'],
            'read_at' => $row['read_at'] !== null ? (string) $row['read_at'] : null,
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    json_success('Notifications retrieved successfully.', $notifications);
}

function get_graduate_notifications_unread_count(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $userId = $authenticated['user_id'];

    $statement = $connection->prepare('SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $result = $statement->get_result()->fetch_assoc();
    $statement->close();

    $unreadCount = is_array($result) ? (int) $result['unread_count'] : 0;
    json_success('Unread notification count retrieved successfully.', ['unread_count' => $unreadCount]);
}

function mark_graduate_notification_read(int $notificationId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $userId = $authenticated['user_id'];

    $checkStmt = $connection->prepare(
        'SELECT notification_id, user_id, title, message, notification_type, is_read, read_at, created_at, updated_at
         FROM notifications
         WHERE notification_id = ? AND user_id = ?
         LIMIT 1'
    );
    $checkStmt->bind_param('ii', $notificationId, $userId);
    $checkStmt->execute();
    $notification = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!is_array($notification)) {
        json_error('Notification was not found.', [], 404);
    }

    try {
        $connection->begin_transaction();

        $updateStmt = $connection->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW(6) WHERE notification_id = ? AND user_id = ?'
        );
        $updateStmt->bind_param('ii', $notificationId, $userId);
        $updateStmt->execute();
        $updateStmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    $fetchStmt = $connection->prepare(
        'SELECT notification_id, user_id, title, message, notification_type, is_read, read_at, created_at, updated_at
         FROM notifications
         WHERE notification_id = ? AND user_id = ?
         LIMIT 1'
    );
    $fetchStmt->bind_param('ii', $notificationId, $userId);
    $fetchStmt->execute();
    $updatedRow = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    $payload = [
        'notification_id' => (int) $updatedRow['notification_id'],
        'user_id' => (int) $updatedRow['user_id'],
        'title' => (string) $updatedRow['title'],
        'message' => (string) $updatedRow['message'],
        'notification_type' => $updatedRow['notification_type'] !== null ? (string) $updatedRow['notification_type'] : null,
        'is_read' => (int) $updatedRow['is_read'],
        'read_at' => $updatedRow['read_at'] !== null ? (string) $updatedRow['read_at'] : null,
        'created_at' => (string) $updatedRow['created_at'],
        'updated_at' => (string) $updatedRow['updated_at'],
    ];

    json_success('Notification marked as read.', $payload);
}

function mark_all_graduate_notifications_read(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $userId = $authenticated['user_id'];

    try {
        $connection->begin_transaction();

        $statement = $connection->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW(6) WHERE user_id = ? AND is_read = 0'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $affectedRows = $statement->affected_rows;
        $statement->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('All notifications marked as read.', ['updated_count' => $affectedRows]);
}

function create_user_notification(mysqli $connection, int $userId, string $title, string $message, ?string $notificationType = 'announcement'): int
{
    return create_mugts_in_app_notification($connection, $userId, $title, $message, $notificationType ?? 'announcement');
}

function delete_graduate_notification(int $notificationId): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $userId = $authenticated['user_id'];

    $checkStmt = $connection->prepare(
        'SELECT notification_id, user_id FROM notifications WHERE notification_id = ? AND user_id = ? LIMIT 1'
    );
    $checkStmt->bind_param('ii', $notificationId, $userId);
    $checkStmt->execute();
    $row = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!is_array($row)) {
        json_error('Notification was not found.', [], 404);
    }

    try {
        $connection->begin_transaction();

        $stmt = $connection->prepare('DELETE FROM notifications WHERE notification_id = ? AND user_id = ?');
        $stmt->bind_param('ii', $notificationId, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Notification deleted successfully.', ['deleted_count' => $affected]);
}

function delete_graduate_notifications_bulk(): never
{
    $authenticated = require_authenticated_user();
    require_role($authenticated, ['graduate']);
    $connection = database_connection();
    $userId = $authenticated['user_id'];

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    $ids = [];
    if (is_array($payload) && isset($payload['ids']) && is_array($payload['ids'])) {
        $ids = array_map('intval', $payload['ids']);
    } elseif (is_array($payload)) {
        // accept direct array of ids
        $ids = array_map('intval', $payload);
    }

    if (empty($ids)) {
        json_error('No notification IDs provided.', [], 400);
    }

    try {
        $connection->begin_transaction();
        $deleted = 0;
        $stmt = $connection->prepare('DELETE FROM notifications WHERE notification_id = ? AND user_id = ?');
        foreach ($ids as $id) {
            $nid = (int) $id;
            $stmt->bind_param('ii', $nid, $userId);
            $stmt->execute();
            $deleted += $stmt->affected_rows;
        }
        $stmt->close();
        $connection->commit();
    } catch (Throwable $exception) {
        rollback_transaction($connection);
        throw $exception;
    }

    json_success('Notifications deleted successfully.', ['deleted_count' => $deleted]);
}
