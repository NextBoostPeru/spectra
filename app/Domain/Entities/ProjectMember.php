<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class ProjectMember
{
    public function __construct(
        private readonly string $projectId,
        private readonly string $companyUserId,
        private readonly string $roleInProject,
    ) {
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'company_user_id' => $this->companyUserId,
            'role_in_project' => $this->roleInProject,
        ];
    }
}
