<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\Assignment;
use App\Domain\Repositories\AssignmentRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class AssignmentRepository extends PdoRepository implements AssignmentRepositoryInterface
{
    public function create(array $data): Assignment
    {
        return $this->guard(function () use ($data): Assignment {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO assignments (id, company_id, project_id, freelancer_id, contract_id, role_title, start_date, end_date, status)
VALUES (:id, :company_id, :project_id, :freelancer_id, :contract_id, :role_title, :start_date, :end_date, :status)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':project_id', $data['project_id']);
            $statement->bindValue(':freelancer_id', $data['freelancer_id']);
            $statement->bindValue(':contract_id', $data['contract_id'] ?? null);
            $statement->bindValue(':role_title', $data['role_title']);
            $statement->bindValue(':start_date', $data['start_date']);
            $statement->bindValue(':end_date', $data['end_date'] ?? null);
            $statement->bindValue(':status', $data['status'] ?? 'active');
            $statement->execute();

            return new Assignment(
                $id,
                $data['company_id'],
                $data['project_id'],
                $data['freelancer_id'],
                $data['role_title'],
                $data['status'] ?? 'active',
                $data['start_date'],
                $data['end_date'] ?? null,
                $data['contract_id'] ?? null,
            );
        });
    }

    public function listByProject(string $projectId, string $companyId): array
    {
        return $this->guard(function () use ($projectId, $companyId): array {
            $query = 'SELECT id, company_id, project_id, freelancer_id, role_title, status, start_date, end_date, contract_id FROM assignments WHERE project_id = :project_id';
            $query = $this->withCompanyScope($query, $companyId, column: 'company_id');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':project_id', $projectId);
            $statement->execute();

            $rows = $statement->fetchAll();

            return array_map(
                static fn (array $row): Assignment => new Assignment(
                    (string) $row['id'],
                    (string) $row['company_id'],
                    (string) $row['project_id'],
                    (string) $row['freelancer_id'],
                    (string) $row['role_title'],
                    (string) $row['status'],
                    (string) $row['start_date'],
                    $row['end_date'] !== null ? (string) $row['end_date'] : null,
                    $row['contract_id'] !== null ? (string) $row['contract_id'] : null,
                ),
                $rows,
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
