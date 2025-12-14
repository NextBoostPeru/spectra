<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class ApprovalStep
{
    public function __construct(
        private readonly string $id,
        private readonly string $approvalRequestId,
        private readonly int $sequenceOrder,
        private readonly string $requiredRoleId,
        private readonly ?string $assignedToCompanyUserId,
        private readonly string $status,
        private readonly ?string $actedByCompanyUserId,
        private readonly ?string $actedAt,
        private readonly ?string $comment,
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

    public function sequenceOrder(): int
    {
        return $this->sequenceOrder;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'approval_request_id' => $this->approvalRequestId,
            'sequence_order' => $this->sequenceOrder,
            'required_role_id' => $this->requiredRoleId,
            'assigned_to_company_user_id' => $this->assignedToCompanyUserId,
            'status' => $this->status,
            'acted_by_company_user_id' => $this->actedByCompanyUserId,
            'acted_at' => $this->actedAt,
            'comment' => $this->comment,
        ];
    }
}
