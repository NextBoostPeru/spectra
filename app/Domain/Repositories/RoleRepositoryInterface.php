<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Role;

interface RoleRepositoryInterface
{
    /**
     * @return list<Role>
     */
    public function listForCompany(string $companyId): array;

    public function find(string $roleId, string $companyId): ?Role;

    public function create(string $companyId, string $name, bool $isSystem = false): Role;
}
