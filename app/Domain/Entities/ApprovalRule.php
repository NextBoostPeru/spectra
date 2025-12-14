<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class ApprovalRule
{
    public function __construct(
        private readonly string $id,
        private readonly string $policyId,
        private readonly ?float $minAmount,
        private readonly ?float $maxAmount,
        private readonly ?int $currencyId,
        private readonly ?array $condition,
        private readonly int $sequenceOrder,
        private readonly string $requiredRoleId,
    ) {
    }

    public function matchesAmount(?float $amount, ?int $currencyId): bool
    {
        if ($amount === null) {
            return true;
        }

        if ($this->currencyId !== null && $currencyId !== null && $this->currencyId !== $currencyId) {
            return false;
        }

        if ($this->minAmount !== null && $amount < $this->minAmount) {
            return false;
        }

        if ($this->maxAmount !== null && $amount > $this->maxAmount) {
            return false;
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'policy_id' => $this->policyId,
            'min_amount' => $this->minAmount,
            'max_amount' => $this->maxAmount,
            'currency_id' => $this->currencyId,
            'condition' => $this->condition,
            'sequence_order' => $this->sequenceOrder,
            'required_role_id' => $this->requiredRoleId,
        ];
    }
}
