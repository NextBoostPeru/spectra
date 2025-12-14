<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Application\Exceptions\ApplicationException;
use App\Application\Users\CreateRoleUseCase;
use App\Application\Users\CreateUserUseCase;
use App\Application\Users\ListCompanyUsersUseCase;
use App\Application\Users\SyncUserRolesUseCase;
use App\Interface\Http\Requests\RequestValidator;
use InvalidArgumentException;

class UserController extends Controller
{
    public function __construct(
        private readonly CreateUserUseCase $createUser,
        private readonly SyncUserRolesUseCase $syncRoles,
        private readonly ListCompanyUsersUseCase $listCompanyUsers,
        private readonly CreateRoleUseCase $createRole,
        private readonly RequestValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function store(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'email' => static fn ($value): bool => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                'password' => static fn ($value): bool => is_string($value) && strlen($value) >= 8,
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'roles' => static fn ($value): bool => $value === null || is_array($value),
            ]);

            $result = ($this->createUser)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function syncRoles(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'user_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'role_ids' => static fn ($value): bool => is_array($value),
            ]);

            $result = ($this->syncRoles)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function listByCompany(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->listCompanyUsers)($payload);

            return $this->ok(['users' => $result]);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function createRole(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'name' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $role = ($this->createRole)($payload);

            return $this->created(['role' => $role]);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
