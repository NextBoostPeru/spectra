<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use App\Domain\Entities\Deliverable;
use App\Domain\Entities\DeliverableReview;
use App\Domain\Repositories\DeliverableRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class DeliverableRepository extends PdoRepository implements DeliverableRepositoryInterface
{
    public function create(array $data): Deliverable
    {
        return $this->guard(function () use ($data): Deliverable {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO deliverables (id, company_id, project_id, assignment_id, title, description, status, due_date)
VALUES (:id, :company_id, :project_id, :assignment_id, :title, :description, :status, :due_date)
SQL);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':project_id', $data['project_id']);
            $statement->bindValue(':assignment_id', $data['assignment_id'] ?? null);
            $statement->bindValue(':title', $data['title']);
            $statement->bindValue(':description', $data['description'] ?? null);
            $statement->bindValue(':status', $data['status'] ?? 'pending');
            $statement->bindValue(':due_date', $data['due_date'] ?? null);
            $statement->execute();

            return $this->find($id, $data['company_id']); // @phpstan-ignore-line
        });
    }

    public function paginate(string $companyId, int $page, int $pageSize, ?string $projectId = null): array
    {
        return $this->guard(function () use ($companyId, $page, $pageSize, $projectId): array {
            $offset = max(0, ($page - 1) * $pageSize);
            $query = 'SELECT id, company_id, project_id, assignment_id, title, description, status, due_date, submitted_at, reviewed_at FROM deliverables';
            $query = $this->withCompanyScope($query, $companyId, 'deliverables');
            if ($projectId !== null) {
                $query .= ' AND project_id = ' . $this->connection->quote($projectId);
            }
            $query .= ' ORDER BY due_date ASC, created_at DESC LIMIT :limit OFFSET :offset';
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':limit', $pageSize, \PDO::PARAM_INT);
            $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $statement->execute();

            $rows = $statement->fetchAll();
            return array_map([$this, 'mapDeliverable'], $rows);
        });
    }

    public function count(string $companyId, ?string $projectId = null): int
    {
        return $this->guard(function () use ($companyId, $projectId): int {
            $query = 'SELECT COUNT(*) as total FROM deliverables';
            $query = $this->withCompanyScope($query, $companyId, 'deliverables');
            if ($projectId !== null) {
                $query .= ' AND project_id = ' . $this->connection->quote($projectId);
            }
            $statement = $this->connection->query($query);
            $row = $statement?->fetch();

            return (int) ($row['total'] ?? 0);
        });
    }

    public function updateStatus(string $deliverableId, string $companyId, string $status, ?string $submittedAt = null, ?string $reviewedAt = null): ?Deliverable
    {
        return $this->guard(function () use ($deliverableId, $companyId, $status, $submittedAt, $reviewedAt): ?Deliverable {
            $query = 'UPDATE deliverables SET status = :status, submitted_at = COALESCE(:submitted_at, submitted_at), reviewed_at = COALESCE(:reviewed_at, reviewed_at) WHERE id = :id';
            $query = $this->withCompanyScope($query, $companyId, 'deliverables');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':status', $status);
            $statement->bindValue(':submitted_at', $submittedAt);
            $statement->bindValue(':reviewed_at', $reviewedAt);
            $statement->bindValue(':id', $deliverableId);
            $statement->execute();

            return $this->find($deliverableId, $companyId);
        });
    }

    public function find(string $deliverableId, string $companyId): ?Deliverable
    {
        return $this->guard(function () use ($deliverableId, $companyId): ?Deliverable {
            $query = 'SELECT id, company_id, project_id, assignment_id, title, description, status, due_date, submitted_at, reviewed_at FROM deliverables WHERE id = :id';
            $query = $this->withCompanyScope($query, $companyId, 'deliverables');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $deliverableId);
            $statement->execute();
            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            return $this->mapDeliverable($row);
        });
    }

    public function addReview(string $deliverableId, string $companyId, array $data): DeliverableReview
    {
        return $this->guard(function () use ($deliverableId, $companyId, $data): DeliverableReview {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO deliverable_reviews (id, deliverable_id, reviewer_company_user_id, decision, score, comments)
VALUES (:id, :deliverable_id, :reviewer_company_user_id, :decision, :score, :comments)
SQL);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':deliverable_id', $deliverableId);
            $statement->bindValue(':reviewer_company_user_id', $data['reviewer_company_user_id']);
            $statement->bindValue(':decision', $data['decision']);
            $statement->bindValue(':score', $data['score'] ?? null);
            $statement->bindValue(':comments', $data['comments'] ?? null);
            $statement->execute();

            $this->updateStatus(
                $deliverableId,
                $companyId,
                $data['decision'] === 'approved' ? 'accepted' : 'in_review',
                $data['decision'] === 'submitted' ? (new DateTimeImmutable())->format('Y-m-d H:i:s') : null,
                (new DateTimeImmutable())->format('Y-m-d H:i:s')
            );

            return new DeliverableReview(
                id: $id,
                deliverableId: $deliverableId,
                reviewerCompanyUserId: $data['reviewer_company_user_id'],
                decision: $data['decision'],
                score: $data['score'] ?? null,
                comments: $data['comments'] ?? null,
                createdAt: (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            );
        });
    }

    private function mapDeliverable(array $row): Deliverable
    {
        return new Deliverable(
            id: (string) $row['id'],
            companyId: (string) $row['company_id'],
            projectId: (string) $row['project_id'],
            assignmentId: $row['assignment_id'] !== null ? (string) $row['assignment_id'] : null,
            title: (string) $row['title'],
            description: $row['description'] !== null ? (string) $row['description'] : null,
            status: (string) $row['status'],
            dueDate: $row['due_date'] !== null ? (string) $row['due_date'] : null,
            submittedAt: $row['submitted_at'] !== null ? (string) $row['submitted_at'] : null,
            reviewedAt: $row['reviewed_at'] !== null ? (string) $row['reviewed_at'] : null,
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
