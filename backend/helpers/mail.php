<?php

declare(strict_types=1);

function notification_email_preference_enabled(mysqli $connection, int $userId, string $preferenceKey): bool
{
    $stmt = $connection->prepare(
        'SELECT preference_key, preference_value
         FROM user_preferences
         WHERE user_id = ? AND preference_key IN (?, \'email_notifications\')'
    );
    $stmt->bind_param('is', $userId, $preferenceKey);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $preferences = [];
    foreach ($rows as $row) {
        $preferences[(string) $row['preference_key']] = strtolower((string) $row['preference_value']);
    }

    // A new, specific preference takes priority; the legacy global preference
    // remains a backwards-compatible fallback for existing accounts.
    $value = $preferences[$preferenceKey] ?? $preferences['email_notifications'] ?? 'true';
    return $value !== 'false';
}

function record_notification_email_delivery(
    mysqli $connection,
    ?int $notificationId,
    int $userId,
    string $status,
    ?string $errorMessage = null,
): void {
    if ($notificationId === null) {
        return;
    }

    $sentAt = $status === 'sent' ? 'NOW(6)' : 'NULL';
    $sql = "INSERT INTO notification_deliveries (notification_id, user_id, channel, status, sent_at, error_message)
            VALUES (?, ?, 'email', ?, $sentAt, ?)
            ON DUPLICATE KEY UPDATE
              status = VALUES(status),
              sent_at = $sentAt,
              error_message = VALUES(error_message),
              updated_at = NOW(6)";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('iiss', $notificationId, $userId, $status, $errorMessage);
    $stmt->execute();
    $stmt->close();
}

function notification_email_already_finalized(mysqli $connection, ?int $notificationId): bool
{
    if ($notificationId === null) {
        return false;
    }

    $stmt = $connection->prepare(
        "SELECT status FROM notification_deliveries WHERE notification_id = ? AND channel = 'email' LIMIT 1"
    );
    $stmt->bind_param('i', $notificationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return is_array($row) && in_array((string) $row['status'], ['sent', 'skipped'], true);
}

function mugts_notification_html(string $title, string $message, string $recipientName, ?string $actionUrl): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeName = htmlspecialchars($recipientName !== '' ? $recipientName : 'Graduate', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $button = $actionUrl === null ? '' : '<p><a href="' . htmlspecialchars($actionUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" class="button">View in Maseno Pathfinder</a></p>';

    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<style>body{margin:0;background:#f4f6f9;color:#1e293b;font-family:Arial,sans-serif}.card{max-width:600px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}.header{padding:24px;background:#0f172a;color:#fff}.header h1{margin:0;font-size:20px}.header p{margin:4px 0 0;color:#cbd5e1}.content{padding:28px;line-height:1.6}.button{display:inline-block;padding:12px 18px;border-radius:8px;background:#2563eb;color:#fff!important;text-decoration:none;font-weight:bold}.footer{padding:16px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px}</style>'
        . '</head><body><div class="card"><div class="header"><h1>Maseno Pathfinder</h1><p>Graduate Tracer System</p></div><div class="content"><h2>' . $safeTitle . '</h2><p>Dear ' . $safeName . ',</p><div>' . $safeMessage . '</div>' . $button . '</div><div class="footer">Maseno Pathfinder · Maseno University Graduate Tracer System</div></div></body></html>';
}

/**
 * Sends one preference-aware, branded email for an already-created in-app notification.
 * Delivery exceptions are intentionally contained: callers must not roll back the event.
 */
function send_mugts_notification_email(
    int $userId,
    string $subject,
    string $title,
    string $message,
    ?string $actionPath = null,
    string $preferenceKey = 'email_system_notifications',
    ?int $notificationId = null,
): bool {
    try {
        $connection = database_connection();
        if (notification_email_already_finalized($connection, $notificationId)) {
            return true;
        }

        $userStmt = $connection->prepare('SELECT email, first_name, last_name FROM users WHERE user_id = ? LIMIT 1');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if (!is_array($user) || !filter_var((string) ($user['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            record_notification_email_delivery($connection, $notificationId, $userId, 'skipped', 'Recipient email is missing or invalid.');
            return false;
        }

        if (!notification_email_preference_enabled($connection, $userId, $preferenceKey)) {
            record_notification_email_delivery($connection, $notificationId, $userId, 'skipped', 'Recipient disabled this email category.');
            return true;
        }

        $config = mugts_mail_config();
        if (!$config['enabled']) {
            record_notification_email_delivery($connection, $notificationId, $userId, 'skipped', 'Email delivery is disabled by MAIL_ENABLED.');
            return true;
        }

        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            record_notification_email_delivery($connection, $notificationId, $userId, 'failed', 'PHPMailer is not installed. Run composer install in backend.');
            return false;
        }
        require_once $autoload;

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            record_notification_email_delivery($connection, $notificationId, $userId, 'failed', 'PHPMailer could not be loaded.');
            return false;
        }

        $recipientName = trim((string) $user['first_name'] . ' ' . (string) $user['last_name']);
        $actionUrl = $actionPath === null ? null : $config['app_url'] . '/' . ltrim($actionPath, '/');
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $config['host'];
        $mailer->Port = $config['port'];
        $mailer->SMTPAuth = $config['username'] !== '';
        $mailer->Username = $config['username'];
        $mailer->Password = $config['password'];
        $mailer->CharSet = 'UTF-8';
        $mailer->isHTML(true);
        $mailer->setFrom($config['from_address'], $config['from_name']);
        $mailer->addAddress((string) $user['email'], $recipientName);
        if ($config['encryption'] === 'ssl') {
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($config['encryption'] === 'tls') {
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mailer->Subject = '[Maseno Pathfinder] ' . $subject;
        $mailer->Body = mugts_notification_html($title, $message, $recipientName, $actionUrl);
        $mailer->AltBody = "Maseno Pathfinder\n\n" . $title . "\n\nDear " . ($recipientName !== '' ? $recipientName : 'Graduate') . ",\n\n" . trim(strip_tags($message)) . ($actionUrl === null ? '' : "\n\nView in Maseno Pathfinder: " . $actionUrl);
        $mailer->send();

        record_notification_email_delivery($connection, $notificationId, $userId, 'sent');
        return true;
    } catch (Throwable $exception) {
        error_log('MUGTS email delivery failed: ' . $exception->getMessage());
        try {
            record_notification_email_delivery(database_connection(), $notificationId, $userId, 'failed', mb_substr($exception->getMessage(), 0, 1000));
        } catch (Throwable) {
            // The event remains successful even if its delivery log is unavailable.
        }
        return false;
    }
}
