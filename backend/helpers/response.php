<?php

declare(strict_types=1);

function json_success(string $message, mixed $data = null, int $statusCode = 200): never
{
    json_response(['success' => true, 'message' => $message, 'data' => $data], $statusCode);
}

/** @param array<string, mixed> $payload */
function json_auth_success(array $payload, int $statusCode = 200): never
{
    json_response($payload, $statusCode);
}

/** @param array<int|string, mixed> $errors */
function json_error(string $message, array $errors = [], int $statusCode = 400): never
{
    json_response(['success' => false, 'message' => $message, 'errors' => $errors], $statusCode);
}

/** @param array<string, mixed> $payload */
function json_response(array $payload, int $statusCode): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
