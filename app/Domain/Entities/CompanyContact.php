<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class CompanyContact
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $name,
        private readonly string $email,
        private readonly ?string $phone,
        private readonly string $type,
        private readonly bool $isPrimary,
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
            'email' => $this->email,
            'phone' => $this->phone,
            'type' => $this->type,
            'is_primary' => $this->isPrimary,
        ];
    }
}
