<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Freelancer;

interface FreelancerRepositoryInterface
{
    public function paginate(int $page, int $pageSize): array;

    public function count(): int;

    public function create(array $data): Freelancer;

    public function findById(string $id): ?Freelancer;
}
