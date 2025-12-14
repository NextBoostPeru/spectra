<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Project
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $name,
        private readonly ?string $description,
        private readonly int $countryId,
        private readonly int $currencyId,
        private readonly string $status,
        private readonly ?string $createdByCompanyUserId = null,
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'name' => $this->name,
            'description' => $this->description,
            'country_id' => $this->countryId,
            'currency_id' => $this->currencyId,
            'status' => $this->status,
            'created_by_company_user_id' => $this->createdByCompanyUserId,
        ];
    }
}
