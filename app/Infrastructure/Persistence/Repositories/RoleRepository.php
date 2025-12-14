<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\Role;
use App\Domain\Repositories\RoleRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class RoleRepository extends PdoRepository implements RoleRepositoryInterface
{
    public function listForCompany(string $companyId): array
    {
        return $this->guard(function () use ($companyId) {
            $statement = $this->connection->prepare('SELECT id, company_id, name, is_system FROM roles WHERE company_id = :company_id ORDER BY is_system DESC, created_at ASC');
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();

            return array_map(fn (array $row): Role => $this->hydrate($row), $statement->fetchAll());
        });
    }

    public function find(string $roleId, string $companyId): ?Role
    {
        return $this->guard(function () use ($roleId, $companyId) {
            $statement = $this->connection->prepare('SELECT id, company_id, name, is_system FROM roles WHERE id = :id AND company_id = :company_id');
            $statement->bindValue(':id', $roleId);
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function create(string $companyId, string $name, bool $isSystem = false): Role
    {
        return $this->guard(function () use ($companyId, $name, $isSystem) {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO roles (id, company_id, name, is_system)
VALUES (:id, :company_id, :name, :is_system)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':name', $name);
            $statement->bindValue(':is_system', $isSystem ? 1 : 0, \PDO::PARAM_BOOL);
            $statement->execute();

            return $this->find($id, $companyId);
        });
    }

    private function hydrate(array $row): Role
    {
        return new Role(
            id: (string) $row['id'],
            companyId: (string) $row['company_id'],
            name: (string) $row['name'],
            isSystem: (bool) $row['is_system'],
        );
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }
}
