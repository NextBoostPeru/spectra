<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class NpsResponse
{
    public function __construct(
        private readonly string $id,
        private readonly string $companyId,
        private readonly string $projectId,
        private readonly string $respondentCompanyUserId,
        private readonly int $score,
        private readonly ?string $comment,
        private readonly string $submittedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->companyId,
            'project_id' => $this->projectId,
            'respondent_company_user_id' => $this->respondentCompanyUserId,
            'score' => $this->score,
            'comment' => $this->comment,
            'submitted_at' => $this->submittedAt,
        ];
    }
}
