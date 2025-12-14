<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\NpsResponse;

interface NpsResponseRepositoryInterface
{
    public function create(array $data): NpsResponse;

    /**
     * @return NpsResponse[]
     */
    public function listByProject(string $companyId, string $projectId): array;
}
