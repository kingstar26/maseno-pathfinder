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
    $caPath = getenv('DB_SSL_CA_PATH');

    if ($caPath !== false && $caPath !== '') {
        if (!is_readable($caPath)) {
            throw new RuntimeException('Database CA certificate is not readable.');
        }

        $connection = mysqli_init();
        mysqli_ssl_set($connection, null, null, $caPath, null, null);
        mysqli_options($connection, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        mysqli_real_connect(
            $connection,
            $host,
            $username,
            $password,
            $database,
            $port,
            null,
            MYSQLI_CLIENT_SSL
        );
    } else {
        $connection = new mysqli(
            $host,
            $username,
            $password,
            $database,
            $port
        );
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}
