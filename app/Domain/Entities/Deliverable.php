<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Deliverable
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $projectId,
        private readonly ?string $assignmentId,
        private readonly string $title,
        private readonly ?string $description,
        private readonly string $status,
        private readonly ?string $dueDate,
        private readonly ?string $submittedAt,
        private readonly ?string $reviewedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'project_id' => $this->projectId,
            'assignment_id' => $this->assignmentId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'submitted_at' => $this->submittedAt,
            'reviewed_at' => $this->reviewedAt,
        ];
    }
}
