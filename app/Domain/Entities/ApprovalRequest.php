<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class ApprovalRequest
{
    /**
     * @param list<ApprovalStep> $steps
     */
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $objectType,
        private readonly string $objectId,
        private readonly string $status,
        private readonly ?string $createdByCompanyUserId,
        private readonly array $steps = [],
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'object_type' => $this->objectType,
            'object_id' => $this->objectId,
            'status' => $this->status,
            'created_by_company_user_id' => $this->createdByCompanyUserId,
            'steps' => array_map(static fn (ApprovalStep $step) => $step->toArray(), $this->steps),
        ];
    }
}
