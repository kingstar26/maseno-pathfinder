<?php

declare(strict_types=1);

function load_mugts_mail_environment(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $environmentFile = __DIR__ . '/../.env';
    if (!is_file($environmentFile) || !is_readable($environmentFile)) {
        return;
    }

    $allowedKeys = [
        'MAIL_ENABLED',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
        'MUGTS_APP_URL',
    ];

    $lines = file($environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (!in_array($key, $allowedKeys, true) || getenv($key) !== false) {
            continue;
        }

        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function mugts_mail_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    load_mugts_mail_environment();

    $enabled = filter_var(getenv('MAIL_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    $config = [
        'enabled' => $enabled,
        'host' => trim((string) (getenv('MAIL_HOST') ?: '')),
        'port' => (int) (getenv('MAIL_PORT') ?: 587),
        'username' => trim((string) (getenv('MAIL_USERNAME') ?: '')),
        'password' => (string) (getenv('MAIL_PASSWORD') ?: ''),
        'encryption' => strtolower(trim((string) (getenv('MAIL_ENCRYPTION') ?: 'tls'))),
        'from_address' => trim((string) (getenv('MAIL_FROM_ADDRESS') ?: 'noreply@maseno.ac.ke')),
        'from_name' => trim((string) (getenv('MAIL_FROM_NAME') ?: 'Maseno Pathfinder')),
        'app_url' => rtrim((string) (getenv('MUGTS_APP_URL') ?: 'http://localhost/maseno-pathfinder/frontend'), '/'),
    ];

    return $config;
}
