<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Interface\Http\Responses\ApiResponse;

abstract class Controller
{
    protected function ok(array $data = [], array $meta = []): string
    {
        return ApiResponse::success($data, $meta);
    }

    protected function created(array $data = []): string
    {
        return ApiResponse::success($data, status: 201);
    }

    protected function error(string $message, int $status = 400, array $errors = []): string
    {
        return ApiResponse::error($message, $status, $errors);
    }
}
