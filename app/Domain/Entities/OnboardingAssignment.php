<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class OnboardingAssignment
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $checklistId,
        private readonly string $subjectType,
        private readonly string $subjectId,
        private readonly string $status,
        private readonly ?string $startedAt = null,
        private readonly ?string $completedAt = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'checklist_id' => $this->checklistId,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
        ];
    }
}
