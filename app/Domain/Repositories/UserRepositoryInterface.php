<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function recordLogin(string $userId, ?string $ip, ?string $userAgent): void;
}
