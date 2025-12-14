<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface ProjectMemberRepositoryInterface
{
    public function isMember(string $projectId, string $companyUserId): bool;
}
