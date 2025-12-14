<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\Company;
use App\Domain\Repositories\CompanyRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class CompanyRepository extends PdoRepository implements CompanyRepositoryInterface
{
    public function paginate(int $page, int $pageSize): array
    {
        return $this->guard(function () use ($page, $pageSize) {
            $offset = max(0, ($page - 1) * $pageSize);
            $query = $this->withSoftDeleteScope('SELECT id, legal_name, trade_name, country_id, default_currency_id, timezone, status, deleted_at FROM companies ORDER BY created_at DESC LIMIT :limit OFFSET :offset');

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll();
        });
    }

    public function count(): int
    {
        return $this->guard(function () {
            $statement = $this->connection->query('SELECT COUNT(*) AS total FROM companies WHERE deleted_at IS NULL');
            $result = $statement?->fetch();

            return (int) ($result['total'] ?? 0);
        });
    }

    public function findById(string $id): ?Company
    {
        return $this->guard(function () use ($id) {
            $query = $this->withSoftDeleteScope('SELECT id, legal_name, trade_name, country_id, default_currency_id, timezone, status, deleted_at FROM companies WHERE id = :id');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $id);
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function create(array $data): Company
    {
        return $this->guard(function () use ($data) {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO companies (id, legal_name, trade_name, country_id, default_currency_id, timezone, status)
VALUES (:id, :legal_name, :trade_name, :country_id, :default_currency_id, :timezone, :status)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':legal_name', $data['legal_name']);
            $statement->bindValue(':trade_name', $data['trade_name'] ?? null);
            $statement->bindValue(':country_id', $data['country_id']);
            $statement->bindValue(':default_currency_id', $data['default_currency_id']);
            $statement->bindValue(':timezone', $data['timezone']);
            $statement->bindValue(':status', $data['status'] ?? 'active');
            $statement->execute();

            return $this->findById($id);
        });
    }

    public function update(string $id, array $data): Company
    {
        return $this->guard(function () use ($id, $data) {
            $fields = [];
            $bindings = [':id' => $id];

            foreach (['legal_name', 'trade_name', 'country_id', 'default_currency_id', 'timezone', 'status'] as $column) {
                if (array_key_exists($column, $data)) {
                    $fields[] = sprintf('%s = :%s', $column, $column);
                    $bindings[':' . $column] = $data[$column];
                }
            }

            if ($fields === []) {
                return $this->findById($id);
            }

            $query = sprintf('UPDATE companies SET %s WHERE id = :id', implode(', ', $fields));
            $statement = $this->connection->prepare($query);

            foreach ($bindings as $key => $value) {
                $statement->bindValue($key, $value);
            }

            $statement->execute();

            return $this->findById($id);
        });
    }

    public function softDelete(string $id): void
    {
        $this->guard(function () use ($id) {
            $statement = $this->connection->prepare('UPDATE companies SET deleted_at = NOW() WHERE id = :id');
            $statement->bindValue(':id', $id);
            $statement->execute();
        });
    }

    private function hydrate(array $row): Company
    {
        $deletedAt = null;

        if (! empty($row['deleted_at'])) {
            $deletedAt = new DateTimeImmutable((string) $row['deleted_at']);
        }

        return new Company(
            id: (string) $row['id'],
            legalName: (string) $row['legal_name'],
            tradeName: $row['trade_name'] !== null ? (string) $row['trade_name'] : null,
            countryId: (int) $row['country_id'],
            defaultCurrencyId: (int) $row['default_currency_id'],
            timezone: (string) $row['timezone'],
            status: (string) $row['status'],
            deletedAt: $deletedAt,
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
