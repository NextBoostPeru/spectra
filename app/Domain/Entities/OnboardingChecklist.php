<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class OnboardingChecklist
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $name,
        private readonly ?string $description = null,
        private readonly ?string $category = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
        ];
    }
}
