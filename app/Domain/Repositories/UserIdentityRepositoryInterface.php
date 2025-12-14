<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\UserIdentity;

interface UserIdentityRepositoryInterface
{
    public function findByProviderSubject(string $provider, string $subject): ?UserIdentity;

    public function linkIdentity(string $userId, string $provider, string $subject, bool $emailVerified): UserIdentity;
}
