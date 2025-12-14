<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\UserRoleRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class UserRoleRepository extends PdoRepository implements UserRoleRepositoryInterface
{
    public function listRoleIds(string $companyUserId): array
    {
        return $this->guard(function () use ($companyUserId) {
            $statement = $this->connection->prepare('SELECT role_id FROM user_roles WHERE company_user_id = :company_user_id');
            $statement->bindValue(':company_user_id', $companyUserId);
            $statement->execute();

            return array_map(static fn (array $row): string => (string) $row['role_id'], $statement->fetchAll());
        });
    }

    public function sync(string $companyUserId, array $roleIds): void
    {
        $this->guard(function () use ($companyUserId, $roleIds) {
            $delete = $this->connection->prepare('DELETE FROM user_roles WHERE company_user_id = :company_user_id');
            $delete->bindValue(':company_user_id', $companyUserId);
            $delete->execute();

            if ($roleIds === []) {
                return;
            }

            $insert = $this->connection->prepare('INSERT INTO user_roles (company_user_id, role_id) VALUES (:company_user_id, :role_id)');

            foreach ($roleIds as $roleId) {
                $insert->bindValue(':company_user_id', $companyUserId);
                $insert->bindValue(':role_id', $roleId);
                $insert->execute();
            }
        });
    }
}
