<?php

declare(strict_types=1);

require_once __DIR__ . '/mail.php';

function database_connection(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    load_mugts_mail_environment();

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = (int) (getenv('DB_PORT') ?: 3306);
    $database = getenv('DB_DATABASE') ?: 'maseno_graduate_tracer';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';

    $connection = new mysqli(
        $host,
        $username,
        $password,
        $database,
        $port
    );

    $connection->set_charset('utf8mb4');

    return $connection;
}