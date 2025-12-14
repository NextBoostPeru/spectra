<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Assignment;

interface AssignmentRepositoryInterface
{
    public function create(array $data): Assignment;

    /**
     * @return list<Assignment>
     */
    public function listByProject(string $projectId, string $companyId): array;
}
