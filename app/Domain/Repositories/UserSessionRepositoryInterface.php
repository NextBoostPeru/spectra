<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\UserSession;

interface UserSessionRepositoryInterface
{
    public function create(string $userId, string $refreshTokenHash, ?string $ip, ?string $userAgent): UserSession;

    public function findActiveByRefreshHash(string $hash): ?UserSession;

    public function rotateToken(string $sessionId, string $newRefreshTokenHash, ?string $ip, ?string $userAgent): UserSession;

    public function revokeByRefreshHash(string $hash): void;
}
