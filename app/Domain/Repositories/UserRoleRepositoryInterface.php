<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface UserRoleRepositoryInterface
{
    /**
     * @return string[]
     */
    public function listRoleIds(string $companyUserId): array;

    /**
     * @param string[] $roleIds
     */
    public function sync(string $companyUserId, array $roleIds): void;
}
