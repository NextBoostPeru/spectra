<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface PermissionRepositoryInterface
{
    /**
     * @return string[] Listado de códigos de permiso para el usuario-company dado.
     */
    public function getPermissionsForCompanyUser(string $companyUserId): array;
}
