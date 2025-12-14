<?php

declare(strict_types=1);

namespace App\Application\Users;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Application\Auth\PasswordHasher;
use App\Domain\Repositories\CompanyRepositoryInterface;
use App\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Domain\Repositories\RoleRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\UserRoleRepositoryInterface;

class CreateUserUseCase implements UseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CompanyRepositoryInterface $companies,
        private readonly CompanyUserRepositoryInterface $companyUsers,
        private readonly RoleRepositoryInterface $roles,
        private readonly UserRoleRepositoryInterface $userRoles,
        private readonly PasswordHasher $hasher,
    ) {
    }

    /**
     * @param array{email:string,password:string,company_id:string,roles?:string[]} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApplicationException('Datos inválidos para usuario.');
        }

        foreach (['email', 'password', 'company_id'] as $field) {
            if (empty($input[$field])) {
                throw new ApplicationException(sprintf('Falta %s', $field));
            }
        }

        $email = strtolower((string) $input['email']);

        if ($this->users->findByEmail($email) !== null) {
            throw new ApplicationException('El correo ya está registrado.');
        }

        if ($this->companies->findById((string) $input['company_id']) === null) {
            throw new ApplicationException('Compañía destino inexistente.');
        }

        $user = $this->users->create($email, $this->hasher->hash((string) $input['password']));
        $membership = $this->companyUsers->createMembership((string) $input['company_id'], $user->id(), 'active', true);

        $roleIds = array_filter($input['roles'] ?? [], static fn ($value): bool => is_string($value) && $value !== '');

        if ($roleIds !== []) {
            $validRoles = [];

            foreach ($roleIds as $roleId) {
                $role = $this->roles->find($roleId, $membership->companyId());

                if ($role !== null) {
                    $validRoles[] = $role->id();
                }
            }

            $this->userRoles->sync($membership->id(), $validRoles);
        }

        return [
            'user' => [
                'id' => $user->id(),
                'email' => $user->email(),
            ],
            'company_user' => [
                'id' => $membership->id(),
                'company_id' => $membership->companyId(),
                'status' => 'active',
                'active_company' => true,
            ],
            'roles' => $roleIds,
        ];
    }
}
