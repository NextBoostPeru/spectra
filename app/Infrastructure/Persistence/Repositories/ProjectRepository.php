<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\Project;
use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class ProjectRepository extends PdoRepository implements ProjectRepositoryInterface
{
    public function paginate(string $companyId, int $page, int $pageSize): array
    {
        return $this->guard(function () use ($companyId, $page, $pageSize): array {
            $offset = max(0, ($page - 1) * $pageSize);
            $query = 'SELECT id, company_id, name, description, country_id, currency_id, status, created_by_company_user_id, deleted_at FROM projects';
            $query = $this->withCompanyScope($query, $companyId, 'projects');
            $query = $this->withSoftDeleteScope($query, 'projects');
            $query .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll();
        });
    }

    public function count(string $companyId): int
    {
        return $this->guard(function () use ($companyId): int {
            $query = 'SELECT COUNT(*) AS total FROM projects';
            $query = $this->withCompanyScope($query, $companyId, 'projects');
            $query = $this->withSoftDeleteScope($query, 'projects');
            $statement = $this->connection->query($query);
            $row = $statement?->fetch();

            return (int) ($row['total'] ?? 0);
        });
    }

    public function create(string $companyId, array $data): Project
    {
        return $this->guard(function () use ($companyId, $data): Project {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO projects (id, company_id, name, description, country_id, currency_id, status, created_by_company_user_id)
VALUES (:id, :company_id, :name, :description, :country_id, :currency_id, :status, :created_by_company_user_id)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':name', $data['name']);
            $statement->bindValue(':description', $data['description'] ?? null);
            $statement->bindValue(':country_id', $data['country_id']);
            $statement->bindValue(':currency_id', $data['currency_id']);
            $statement->bindValue(':status', $data['status'] ?? 'active');
            $statement->bindValue(':created_by_company_user_id', $data['created_by_company_user_id'] ?? null);
            $statement->execute();

            return $this->findById($id, $companyId);
        });
    }

    public function update(string $projectId, string $companyId, array $data): Project
    {
        return $this->guard(function () use ($projectId, $companyId, $data): Project {
            $fields = [];
            $bindings = [':project_id' => $projectId, ':company_id' => $companyId];

            foreach (['name', 'description', 'country_id', 'currency_id', 'status'] as $column) {
                if (array_key_exists($column, $data)) {
                    $fields[] = sprintf('%s = :%s', $column, $column);
                    $bindings[':' . $column] = $data[$column];
                }
            }

            if ($fields !== []) {
                $query = sprintf('UPDATE projects SET %s WHERE id = :project_id AND company_id = :company_id', implode(', ', $fields));
                $statement = $this->connection->prepare($query);

                foreach ($bindings as $key => $value) {
                    $statement->bindValue($key, $value);
                }

                $statement->execute();
            }

            return $this->findById($projectId, $companyId);
        });
    }

    public function findById(string $projectId, string $companyId): ?Project
    {
        return $this->guard(function () use ($projectId, $companyId): ?Project {
            $query = 'SELECT id, company_id, name, description, country_id, currency_id, status, created_by_company_user_id, deleted_at FROM projects WHERE id = :id';
            $query = $this->withCompanyScope($query, $companyId, 'projects');
            $query = $this->withSoftDeleteScope($query, 'projects');

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $projectId);
            $statement->execute();

            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            $deletedAt = null;
            if (! empty($row['deleted_at'])) {
                $deletedAt = new DateTimeImmutable((string) $row['deleted_at']);
            }

            return new Project(
                id: (string) $row['id'],
                companyId: (string) $row['company_id'],
                name: (string) $row['name'],
                description: $row['description'] !== null ? (string) $row['description'] : null,
                countryId: (int) $row['country_id'],
                currencyId: (int) $row['currency_id'],
                status: (string) $row['status'],
                createdByCompanyUserId: $row['created_by_company_user_id'] !== null ? (string) $row['created_by_company_user_id'] : null,
                deletedAt: $deletedAt,
            );
        });
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
