<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\DomainException;

class Company
{
    public function __construct(
        private readonly string $id,
        private readonly string $legalName,
        private readonly ?string $tradeName,
        private readonly int $countryId,
        private readonly int $defaultCurrencyId,
        private readonly string $timezone,
        private readonly string $status,
        private readonly ?\DateTimeImmutable $deletedAt = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function tradeName(): ?string
    {
        return $this->tradeName;
    }

    public function countryId(): int
    {
        return $this->countryId;
    }

    public function defaultCurrencyId(): int
    {
        return $this->defaultCurrencyId;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function assertActive(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainException('La compañía fue eliminada.');
        }

        if ($this->status !== 'active') {
            throw new DomainException('La compañía no está activa.');
        }
    }
}
