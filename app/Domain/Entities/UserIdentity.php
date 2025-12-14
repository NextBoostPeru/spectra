<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class UserIdentity
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $provider,
        private readonly string $providerSubject,
        private readonly bool $emailVerified,
    ) {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function providerSubject(): string
    {
        return $this->providerSubject;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }
}
