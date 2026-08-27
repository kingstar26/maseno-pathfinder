<?php

declare(strict_types=1);

function database_connection(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $connection = new mysqli(
        "localhost",
        "root",
        "",
        "maseno_graduate_tracer",
        3306
    );

    $connection->set_charset("utf8mb4");

    return $connection;
}
