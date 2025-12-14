<?php

declare(strict_types=1);

namespace App\Application\Authorization;

use App\Domain\Repositories\PermissionRepositoryInterface;

class AuthorizationService
{
    /** @var array<string, array<string, bool>> */
    private array $permissionCache = [];

    public function __construct(private readonly PermissionRepositoryInterface $permissions)
    {
    }

    public function hasCompanyPermission(string $companyUserId, string $permissionCode, string $platformRole = 'none'): bool
    {
        if ($platformRole === 'super_admin') {
            return true;
        }

        $permissions = $this->permissionsForCompanyUser($companyUserId);

        return isset($permissions[$permissionCode]);
    }

    /**
     * @return array<string, bool>
     */
    private function permissionsForCompanyUser(string $companyUserId): array
    {
        if (! isset($this->permissionCache[$companyUserId])) {
            $codes = $this->permissions->getPermissionsForCompanyUser($companyUserId);
            $this->permissionCache[$companyUserId] = array_fill_keys($codes, true);
        }

        return $this->permissionCache[$companyUserId];
    }
}
