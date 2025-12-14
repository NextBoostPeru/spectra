<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Timesheet
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $assignmentId,
        private readonly string $workDate,
        private readonly float $hours,
        private readonly ?string $description,
        private readonly string $status,
        private readonly ?string $submittedAt,
        private readonly ?string $approvedByCompanyUserId,
        private readonly ?string $approvedAt,
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

    public function assignmentId(): string
    {
        return $this->assignmentId;
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
            'assignment_id' => $this->assignmentId,
            'work_date' => $this->workDate,
            'hours' => $this->hours,
            'description' => $this->description,
            'status' => $this->status,
            'submitted_at' => $this->submittedAt,
            'approved_by_company_user_id' => $this->approvedByCompanyUserId,
            'approved_at' => $this->approvedAt,
        ];
    }
}
