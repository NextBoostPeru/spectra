<?php

declare(strict_types=1);

namespace App\Application\Authorization;

use App\Domain\Repositories\ProjectMemberRepositoryInterface;

class ProjectPolicy
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly ProjectMemberRepositoryInterface $projectMembers,
    ) {
    }

    public function canView(string $projectId, string $companyUserId, string $platformRole = 'none'): bool
    {
        if ($this->authorization->hasCompanyPermission($companyUserId, 'projects.view_all', $platformRole)) {
            return true;
        }

        if ($this->authorization->hasCompanyPermission($companyUserId, 'projects.manage', $platformRole)) {
            return true;
        }

        return $this->projectMembers->isMember($projectId, $companyUserId);
    }
}
