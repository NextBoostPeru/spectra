<?php

declare(strict_types=1);

namespace App\Application\Users;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Domain\Repositories\RoleRepositoryInterface;
use App\Domain\Repositories\UserRoleRepositoryInterface;
use App\Domain\Exceptions\DomainException;

class SyncUserRolesUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyUserRepositoryInterface $companyUsers,
        private readonly RoleRepositoryInterface $roles,
        private readonly UserRoleRepositoryInterface $userRoles,
    ) {
    }

    /**
     * @param array{user_id:string,company_id:string,role_ids:string[]} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['user_id']) || empty($input['company_id']) || ! is_array($input['role_ids'] ?? null)) {
            throw new ApplicationException('Datos inválidos para asignación de roles.');
        }

        try {
            $target = $this->companyUsers->assertMembership((string) $input['user_id'], (string) $input['company_id']);
        } catch (DomainException $exception) {
            throw new ApplicationException($exception->getMessage(), previous: $exception);
        }

        $roleIds = array_filter($input['role_ids'], static fn ($value): bool => is_string($value) && $value !== '');
        $valid = [];

        foreach ($roleIds as $roleId) {
            $role = $this->roles->find($roleId, $target->companyId());

            if ($role !== null) {
                $valid[] = $role->id();
            }
        }

        $this->userRoles->sync($target->id(), $valid);

        return [
            'company_user_id' => $target->id(),
            'role_ids' => $valid,
        ];
    }
}
