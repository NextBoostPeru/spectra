<?php

declare(strict_types=1);

namespace App\Interface\Http\Responses;

use App\Application\Pagination\PaginationResult;

class ApiResponse
{
    /**
     * @param array<string, mixed>|PaginationResult $data
     * @param array<string, scalar|array|null> $meta
     */
    public static function success(array|PaginationResult $data = [], array $meta = [], int $status = 200): string
    {
        if ($data instanceof PaginationResult) {
            $payload = $data->toArray();
            $data = $payload['data'];
            $meta = array_merge($meta, $payload['meta']);
        }

        http_response_code($status);

        return json_encode([
            'status' => 'success',
            'data' => $data,
            'meta' => $meta,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, scalar|array|null> $errors
     */
    public static function error(string $message, int $status = 400, array $errors = []): string
    {
        http_response_code($status);

        return json_encode([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], JSON_THROW_ON_ERROR);
    }
}
