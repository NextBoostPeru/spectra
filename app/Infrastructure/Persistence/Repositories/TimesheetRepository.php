<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\Timesheet;
use App\Domain\Repositories\TimesheetRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class TimesheetRepository extends PdoRepository implements TimesheetRepositoryInterface
{
    public function create(array $data): Timesheet
    {
        return $this->guard(function () use ($data) {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO timesheets (id, company_id, assignment_id, work_date, hours, description, status, submitted_at)
VALUES (:id, :company_id, :assignment_id, :work_date, :hours, :description, :status, :submitted_at)
SQL);

            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':assignment_id', $data['assignment_id']);
            $statement->bindValue(':work_date', $data['work_date']);
            $statement->bindValue(':hours', $data['hours']);
            $statement->bindValue(':description', $data['description']);
            $statement->bindValue(':status', $data['status']);
            $statement->bindValue(':submitted_at', $data['submitted_at']);
            $statement->execute();

            return $this->findById($id, $data['company_id']);
        });
    }

    public function findById(string $id, string $companyId): ?Timesheet
    {
        return $this->guard(function () use ($id, $companyId) {
            $query = 'SELECT id, company_id, assignment_id, work_date, hours, description, status, submitted_at, approved_by_company_user_id, approved_at FROM timesheets WHERE id = :id AND company_id = :company_id';
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();

            $row = $statement->fetch();

            if ($row === false) {
                return null;
            }

            return $this->hydrate($row);
        });
    }

    public function listByAssignment(string $assignmentId, string $companyId, int $page, int $pageSize): array
    {
        return $this->guard(function () use ($assignmentId, $companyId, $page, $pageSize) {
            $offset = max(0, ($page - 1) * $pageSize);
            $query = 'SELECT id, company_id, assignment_id, work_date, hours, description, status, submitted_at, approved_by_company_user_id, approved_at FROM timesheets WHERE assignment_id = :assignment_id AND company_id = :company_id ORDER BY work_date DESC LIMIT :limit OFFSET :offset';
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':assignment_id', $assignmentId);
            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $statement->execute();

            $rows = $statement->fetchAll();

            return array_map(fn ($row) => $this->hydrate($row), $rows);
        });
    }

    public function countByAssignment(string $assignmentId, string $companyId): int
    {
        return $this->guard(function () use ($assignmentId, $companyId) {
            $statement = $this->connection->prepare('SELECT COUNT(*) AS total FROM timesheets WHERE assignment_id = :assignment_id AND company_id = :company_id');
            $statement->bindValue(':assignment_id', $assignmentId);
            $statement->bindValue(':company_id', $companyId);
            $statement->execute();
            $row = $statement->fetch();

            return (int) ($row['total'] ?? 0);
        });
    }

    public function update(string $id, string $companyId, array $data): Timesheet
    {
        return $this->guard(function () use ($id, $companyId, $data) {
            $fields = [];
            $bindings = [
                ':id' => $id,
                ':company_id' => $companyId,
            ];

            foreach (['work_date', 'hours', 'description', 'status', 'submitted_at', 'approved_by_company_user_id', 'approved_at'] as $column) {
                if (array_key_exists($column, $data)) {
                    $fields[] = sprintf('%s = :%s', $column, $column);
                    $bindings[':' . $column] = $data[$column];
                }
            }

            if ($fields !== []) {
                $query = sprintf('UPDATE timesheets SET %s WHERE id = :id AND company_id = :company_id', implode(', ', $fields));
                $statement = $this->connection->prepare($query);
                foreach ($bindings as $key => $value) {
                    $statement->bindValue($key, $value);
                }

                $statement->execute();
            }

            $refreshed = $this->findById($id, $companyId);
            if ($refreshed === null) {
                throw new \RuntimeException('No se pudo actualizar el timesheet.');
            }

            return $refreshed;
        });
    }

    private function hydrate(array $row): Timesheet
    {
        return new Timesheet(
            id: (string) $row['id'],
            companyId: (string) $row['company_id'],
            assignmentId: (string) $row['assignment_id'],
            workDate: (string) $row['work_date'],
            hours: (float) $row['hours'],
            description: $row['description'] !== null ? (string) $row['description'] : null,
            status: (string) $row['status'],
            submittedAt: $row['submitted_at'] !== null ? (string) $row['submitted_at'] : null,
            approvedByCompanyUserId: $row['approved_by_company_user_id'] !== null ? (string) $row['approved_by_company_user_id'] : null,
            approvedAt: $row['approved_at'] !== null ? (string) $row['approved_at'] : null,
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
