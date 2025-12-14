<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Assignment
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $projectId,
        private readonly string $freelancerId,
        private readonly string $roleTitle,
        private readonly string $status,
        private readonly string $startDate,
        private readonly ?string $endDate = null,
        private readonly ?string $contractId = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'project_id' => $this->projectId,
            'freelancer_id' => $this->freelancerId,
            'role_title' => $this->roleTitle,
            'status' => $this->status,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'contract_id' => $this->contractId,
        ];
    }
}
