<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class OnboardingAssignmentItem
{
    public function __construct(
        private readonly string $id,
        private readonly string $assignmentId,
        private readonly string $itemId,
        private readonly string $status,
        private readonly ?string $assigneeCompanyUserId = null,
        private readonly ?string $dueDate = null,
        private readonly ?string $completedAt = null,
        private readonly ?string $notes = null,
        private readonly ?string $evidenceUrl = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignmentId,
            'item_id' => $this->itemId,
            'status' => $this->status,
            'assignee_company_user_id' => $this->assigneeCompanyUserId,
            'due_date' => $this->dueDate,
            'completed_at' => $this->completedAt,
            'notes' => $this->notes,
            'evidence_url' => $this->evidenceUrl,
        ];
    }
}
