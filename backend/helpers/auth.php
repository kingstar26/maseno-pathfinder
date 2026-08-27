<?php

declare(strict_types=1);

function bearer_token(): ?string
{
    $header = '';

    // Apache/XAMPP may expose Authorization through getallheaders()
    if (function_exists('getallheaders')) {
        $headers = getallheaders();

        if (isset($headers['Authorization'])) {
            $header = $headers['Authorization'];
        }
    }

    // Fallback methods
    if ($header === '') {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
    }

    if (!is_string($header) || preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
        return null;
    }

    $token = trim($matches[1]);

    return $token === '' ? null : $token;
}

function session_token_hash(string $token): string
{
    return hash('sha256', $token);
}