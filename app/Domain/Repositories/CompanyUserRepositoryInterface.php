<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\CompanyUser;

interface CompanyUserRepositoryInterface
{
    public function findActiveForUser(string $userId): ?CompanyUser;

    public function assertMembership(string $userId, string $companyId): CompanyUser;

    public function setActiveCompany(string $userId, string $companyId): CompanyUser;
}
