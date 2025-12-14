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
            'data' => $data,
            'meta' => array_merge(['status' => $status], $meta),
            'errors' => [],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, scalar|array|null> $errors
     */
    public static function error(string $message, int $status = 400, array $errors = [], ?string $code = null): string
    {
        http_response_code($status);

        return json_encode([
            'data' => null,
            'meta' => [
                'status' => $status,
                'message' => $message,
                'error_code' => $code ?? self::errorCodeForStatus($status),
            ],
            'errors' => $errors,
        ], JSON_THROW_ON_ERROR);
    }

    private static function errorCodeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthorized',
            403 => 'forbidden',
            404 => 'not_found',
            409 => 'conflict',
            422 => 'validation_failed',
            429 => 'too_many_requests',
            default => 'server_error',
        };
    }
}
