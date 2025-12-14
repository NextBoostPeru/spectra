<?php

namespace App\Support;

class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public static function error(string $message, int $status = 400, array $details = []): void
    {
        self::json([
            'error' => $message,
            'details' => $details,
        ], $status);
    }
}
