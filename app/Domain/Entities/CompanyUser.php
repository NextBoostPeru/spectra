<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\DomainException;

class CompanyUser
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $userId,
        private readonly string $status,
        private readonly bool $isActive,
        private readonly ?\DateTimeImmutable $deletedAt = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function companyId(): string
    {
        return $this->companyId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function assertUsable(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainException('La relación con la empresa fue eliminada.');
        }

        if ($this->status !== 'active') {
            throw new DomainException('El usuario no está activo en la empresa seleccionada.');
        }
    }
}
