<?php

declare(strict_types=1);

namespace App\Application\Users;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\UserRoleRepositoryInterface;

class ListCompanyUsersUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyUserRepositoryInterface $companyUsers,
        private readonly UserRepositoryInterface $users,
        private readonly UserRoleRepositoryInterface $userRoles,
    ) {
    }

    /**
     * @param array{company_id:string} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['company_id'])) {
            throw new ApplicationException('company_id requerido');
        }

        $memberships = $this->companyUsers->listForCompany((string) $input['company_id']);

        return array_map(function ($membership) {
            $user = $this->users->findById($membership->userId());

            return [
                'company_user_id' => $membership->id(),
                'user_id' => $membership->userId(),
                'email' => $user?->email(),
                'status' => $membership->status(),
                'active_company' => $membership->isActive(),
                'roles' => $this->userRoles->listRoleIds($membership->id()),
            ];
        }, $memberships);
    }
}
