<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ProjectMember;

interface ProjectMemberRepositoryInterface
{
    public function isMember(string $projectId, string $companyUserId): bool;

    public function addMember(string $projectId, string $companyUserId, string $role): ProjectMember;

    /**
     * @return list<ProjectMember>
     */
    public function listByProject(string $projectId, string $companyId): array;
}
