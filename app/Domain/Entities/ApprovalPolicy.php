<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class ApprovalPolicy
{
    /**
     * @param list<ApprovalRule> $rules
     */
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $name,
        private readonly string $objectType,
        private readonly bool $isActive,
        private readonly array $rules = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'name' => $this->name,
            'object_type' => $this->objectType,
            'is_active' => $this->isActive,
            'rules' => array_map(static fn (ApprovalRule $rule) => $rule->toArray(), $this->rules),
        ];
    }
}
