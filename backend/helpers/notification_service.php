<?php

declare(strict_types=1);

function create_mugts_in_app_notification(mysqli $connection, int $userId, string $title, string $message, string $notificationType = 'system'): int
{
    $stmt = $connection->prepare(
        'INSERT INTO notifications (user_id, title, message, notification_type, is_read) VALUES (?, ?, ?, ?, 0)'
    );
    $stmt->bind_param('isss', $userId, $title, $message, $notificationType);
    $stmt->execute();
    $notificationId = (int) $connection->insert_id;
    $stmt->close();

    return $notificationId;
}
