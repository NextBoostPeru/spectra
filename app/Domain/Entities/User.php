<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\DomainException;

class User
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly string $status,
        private readonly string $platformRole,
        private readonly ?\DateTimeImmutable $deletedAt = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function assertCanLogin(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainException('La cuenta fue eliminada.');
        }

        if ($this->status === 'disabled') {
            throw new DomainException('La cuenta está deshabilitada.');
        }

        if ($this->status === 'locked') {
            throw new DomainException('La cuenta está bloqueada temporalmente.');
        }
    }
}
