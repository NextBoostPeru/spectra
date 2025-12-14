<?php

declare(strict_types=1);

namespace App\Application\Users;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyRepositoryInterface;
use App\Domain\Repositories\RoleRepositoryInterface;

class CreateRoleUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly RoleRepositoryInterface $roles,
    ) {
    }

    /**
     * @param array{company_id:string,name:string} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['company_id']) || empty($input['name'])) {
            throw new ApplicationException('company_id y nombre son obligatorios');
        }

        $company = $this->companies->findById((string) $input['company_id']);

        if ($company === null) {
            throw new ApplicationException('Compañía no encontrada.');
        }

        $role = $this->roles->create($company->id(), (string) $input['name']);

        return [
            'id' => $role->id(),
            'name' => $role->name(),
            'company_id' => $role->companyId(),
            'is_system' => $role->isSystem(),
        ];
    }
}
