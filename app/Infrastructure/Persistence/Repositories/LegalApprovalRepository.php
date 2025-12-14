<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Repositories\LegalApprovalRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class LegalApprovalRepository extends PdoRepository implements LegalApprovalRepositoryInterface
{
    public function create(array $data): void
    {
        $this->guard(function () use ($data): void {
            $id = $data['id'] ?? $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO legal_approvals (id, company_id, contract_id, contract_version_id, status, reviewed_by_company_user_id, reviewed_at, comment)
VALUES (:id, :company_id, :contract_id, :contract_version_id, :status, :reviewed_by_company_user_id, :reviewed_at, :comment)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':contract_id', $data['contract_id']);
            $statement->bindValue(':contract_version_id', $data['contract_version_id'] ?? null);
            $statement->bindValue(':status', $data['status']);
            $statement->bindValue(':reviewed_by_company_user_id', $data['reviewed_by_company_user_id'] ?? null);
            $statement->bindValue(':reviewed_at', $data['reviewed_at'] ?? null);
            $statement->bindValue(':comment', $data['comment'] ?? null);
            $statement->execute();
        });
    }

    public function update(string $id, array $data): void
    {
        $this->guard(function () use ($id, $data): void {
            if ($data === []) {
                return;
            }

            $fields = [];
            $bindings = [':id' => $id];

            foreach ($data as $column => $value) {
                $fields[] = sprintf('%s = :%s', $column, $column);
                $bindings[':' . $column] = $value;
            }

            $statement = $this->connection->prepare(sprintf('UPDATE legal_approvals SET %s WHERE id = :id', implode(', ', $fields)));

            foreach ($bindings as $key => $value) {
                $statement->bindValue($key, $value);
            }

            $statement->execute();
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
