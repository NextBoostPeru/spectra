<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Project;

interface ProjectRepositoryInterface
{
    public function paginate(string $companyId, int $page, int $pageSize): array;

    public function count(string $companyId): int;

    public function create(string $companyId, array $data): Project;

    public function update(string $projectId, string $companyId, array $data): Project;

    public function findById(string $projectId, string $companyId): ?Project;
}
