<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

interface Middleware
{
    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function __invoke(array $request, callable $next): mixed;
}
