<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\AccessProvision;

interface AccessProvisionRepositoryInterface
{
    public function create(array $data): AccessProvision;

    /**
     * @return AccessProvision[]
     */
    public function listByAssignmentItem(string $assignmentItemId): array;
}
