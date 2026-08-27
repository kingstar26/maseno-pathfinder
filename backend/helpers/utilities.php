<?php

declare(strict_types=1);

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';
    $scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    if ($scriptDirectory !== '' && $scriptDirectory !== '.' && str_starts_with($path, $scriptDirectory)) {
        $path = substr($path, strlen($scriptDirectory)) ?: '/';
    }

    return '/' . ltrim($path, '/');
}

function apply_cors_headers(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    $configuredOrigins = getenv('MUGTS_CORS_ALLOWED_ORIGINS');

    if (!is_string($origin) || $configuredOrigins === false || trim($configuredOrigins) === '') {
        return;
    }

    $allowedOrigins = array_filter(array_map('trim', explode(',', $configuredOrigins)));
    if (!in_array($origin, $allowedOrigins, true)) {
        return;
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 600');
}
