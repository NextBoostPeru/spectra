<?php

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use DateInterval;
use DateTimeImmutable;
use App\Domain\Entities\OnboardingAssignment;
use App\Domain\Entities\OnboardingAssignmentItem;
use App\Domain\Repositories\OnboardingAssignmentRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class OnboardingAssignmentRepository extends PdoRepository implements OnboardingAssignmentRepositoryInterface
{
    public function create(array $data): OnboardingAssignment
    {
        return $this->guard(function () use ($data): OnboardingAssignment {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO onboarding_assignments (id, company_id, checklist_id, subject_type, subject_id, status, started_at)
VALUES (:id, :company_id, :checklist_id, :subject_type, :subject_id, :status, :started_at)
SQL);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $data['company_id']);
            $statement->bindValue(':checklist_id', $data['checklist_id']);
            $statement->bindValue(':subject_type', $data['subject_type']);
            $statement->bindValue(':subject_id', $data['subject_id']);
            $statement->bindValue(':status', $data['status'] ?? 'pending');
            $statement->bindValue(':started_at', $data['started_at'] ?? (new DateTimeImmutable())->format('Y-m-d H:i:s'));
            $statement->execute();

            return $this->findWithItems($id, $data['company_id'])['assignment']; // @phpstan-ignore-line
        });
    }

    public function createItems(string $assignmentId, array $items, ?string $startDate = null): void
    {
        $this->guard(function () use ($assignmentId, $items, $startDate): void {
            $start = $startDate !== null ? new DateTimeImmutable($startDate) : new DateTimeImmutable();

            foreach ($items as $item) {
                $dueDate = null;
                if (isset($item['due_days'])) {
                    $dueDate = $start->add(new DateInterval(sprintf('P%dD', (int) $item['due_days'])))->format('Y-m-d');
                }

                $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO onboarding_assignment_items (id, assignment_id, item_id, status, due_date)
VALUES (:id, :assignment_id, :item_id, :status, :due_date)
SQL);
                $statement->bindValue(':id', $this->uuid());
                $statement->bindValue(':assignment_id', $assignmentId);
                $statement->bindValue(':item_id', $item['item_id']);
                $statement->bindValue(':status', 'pending');
                $statement->bindValue(':due_date', $dueDate);
                $statement->execute();
            }
        });
    }

    public function updateAssignmentStatus(string $assignmentId, string $companyId, string $status, ?string $completedAt = null): void
    {
        $this->guard(function () use ($assignmentId, $companyId, $status, $completedAt): void {
            $query = 'UPDATE onboarding_assignments SET status = :status, completed_at = :completed_at WHERE id = :id';
            $query = $this->withCompanyScope($query, $companyId, 'onboarding_assignments');

            $statement = $this->connection->prepare($query);
            $statement->bindValue(':status', $status);
            $statement->bindValue(':completed_at', $completedAt);
            $statement->bindValue(':id', $assignmentId);
            $statement->execute();
        });
    }

    public function updateItem(string $assignmentItemId, string $companyId, array $data): ?OnboardingAssignmentItem
    {
        return $this->guard(function () use ($assignmentItemId, $companyId, $data): ?OnboardingAssignmentItem {
            $fields = [];
            $bindings = [
                ':assignment_item_id' => $assignmentItemId,
            ];

            foreach (['status', 'assignee_company_user_id', 'due_date', 'completed_at', 'notes', 'evidence_url'] as $column) {
                if (array_key_exists($column, $data)) {
                    $fields[] = sprintf('%s = :%s', $column, $column);
                    $bindings[':' . $column] = $data[$column];
                }
            }

            if ($fields !== []) {
                $query = 'UPDATE onboarding_assignment_items oai
                    INNER JOIN onboarding_assignments oa ON oa.id = oai.assignment_id
                    SET ' . implode(', ', $fields) . ' WHERE oai.id = :assignment_item_id';
                $query = $this->withCompanyScope($query, $companyId, 'oa', 'company_id');
                $statement = $this->connection->prepare($query);
                foreach ($bindings as $key => $value) {
                    $statement->bindValue($key, $value);
                }
                $statement->execute();
            }

            return $this->findItem($assignmentItemId, $companyId);
        });
    }

    public function findWithItems(string $assignmentId, string $companyId): ?array
    {
        return $this->guard(function () use ($assignmentId, $companyId): ?array {
            $assignmentQuery = 'SELECT id, company_id, checklist_id, subject_type, subject_id, status, started_at, completed_at FROM onboarding_assignments WHERE id = :id';
            $assignmentQuery = $this->withCompanyScope($assignmentQuery, $companyId, 'onboarding_assignments');
            $assignmentStatement = $this->connection->prepare($assignmentQuery);
            $assignmentStatement->bindValue(':id', $assignmentId);
            $assignmentStatement->execute();
            $assignmentRow = $assignmentStatement->fetch();
            if ($assignmentRow === false) {
                return null;
            }

            $itemsQuery = 'SELECT oai.id, oai.assignment_id, oai.item_id, oai.status, oai.assignee_company_user_id, oai.due_date, oai.completed_at, oai.notes, oai.evidence_url
                FROM onboarding_assignment_items oai
                INNER JOIN onboarding_assignments oa ON oa.id = oai.assignment_id
                WHERE oa.id = :assignment_id';
            $itemsQuery = $this->withCompanyScope($itemsQuery, $companyId, 'oa', 'company_id');
            $itemsQuery .= ' ORDER BY oai.id';
            $itemsStatement = $this->connection->prepare($itemsQuery);
            $itemsStatement->bindValue(':assignment_id', $assignmentId);
            $itemsStatement->execute();

            $items = [];
            foreach ($itemsStatement->fetchAll() as $row) {
                $items[] = new OnboardingAssignmentItem(
                    id: (string) $row['id'],
                    assignmentId: (string) $row['assignment_id'],
                    itemId: (string) $row['item_id'],
                    status: (string) $row['status'],
                    assigneeCompanyUserId: $row['assignee_company_user_id'] !== null ? (string) $row['assignee_company_user_id'] : null,
                    dueDate: $row['due_date'] !== null ? (string) $row['due_date'] : null,
                    completedAt: $row['completed_at'] !== null ? (string) $row['completed_at'] : null,
                    notes: $row['notes'] !== null ? (string) $row['notes'] : null,
                    evidenceUrl: $row['evidence_url'] !== null ? (string) $row['evidence_url'] : null,
                );
            }

            return [
                'assignment' => new OnboardingAssignment(
                    id: (string) $assignmentRow['id'],
                    companyId: (string) $assignmentRow['company_id'],
                    checklistId: (string) $assignmentRow['checklist_id'],
                    subjectType: (string) $assignmentRow['subject_type'],
                    subjectId: (string) $assignmentRow['subject_id'],
                    status: (string) $assignmentRow['status'],
                    startedAt: $assignmentRow['started_at'] !== null ? (string) $assignmentRow['started_at'] : null,
                    completedAt: $assignmentRow['completed_at'] !== null ? (string) $assignmentRow['completed_at'] : null,
                ),
                'items' => $items,
            ];
        });
    }

    public function listForSubject(string $companyId, string $subjectType, string $subjectId): array
    {
        return $this->guard(function () use ($companyId, $subjectType, $subjectId): array {
            $query = 'SELECT id, company_id, checklist_id, subject_type, subject_id, status, started_at, completed_at FROM onboarding_assignments WHERE subject_type = :subject_type AND subject_id = :subject_id';
            $query = $this->withCompanyScope($query, $companyId, 'onboarding_assignments');
            $query .= ' ORDER BY started_at DESC';
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':subject_type', $subjectType);
            $statement->bindValue(':subject_id', $subjectId);
            $statement->execute();

            $rows = $statement->fetchAll();
            $assignments = [];
            foreach ($rows as $row) {
                $assignments[] = new OnboardingAssignment(
                    id: (string) $row['id'],
                    companyId: (string) $row['company_id'],
                    checklistId: (string) $row['checklist_id'],
                    subjectType: (string) $row['subject_type'],
                    subjectId: (string) $row['subject_id'],
                    status: (string) $row['status'],
                    startedAt: $row['started_at'] !== null ? (string) $row['started_at'] : null,
                    completedAt: $row['completed_at'] !== null ? (string) $row['completed_at'] : null,
                );
            }

            return $assignments;
        });
    }

    private function findItem(string $assignmentItemId, string $companyId): ?OnboardingAssignmentItem
    {
        $query = 'SELECT oai.id, oai.assignment_id, oai.item_id, oai.status, oai.assignee_company_user_id, oai.due_date, oai.completed_at, oai.notes, oai.evidence_url
            FROM onboarding_assignment_items oai
            INNER JOIN onboarding_assignments oa ON oa.id = oai.assignment_id
            WHERE oai.id = :assignment_item_id';
        $query = $this->withCompanyScope($query, $companyId, 'oa', 'company_id');
        $statement = $this->connection->prepare($query);
        $statement->bindValue(':assignment_item_id', $assignmentItemId);
        $statement->execute();
        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }

        return new OnboardingAssignmentItem(
            id: (string) $row['id'],
            assignmentId: (string) $row['assignment_id'],
            itemId: (string) $row['item_id'],
            status: (string) $row['status'],
            assigneeCompanyUserId: $row['assignee_company_user_id'] !== null ? (string) $row['assignee_company_user_id'] : null,
            dueDate: $row['due_date'] !== null ? (string) $row['due_date'] : null,
            completedAt: $row['completed_at'] !== null ? (string) $row['completed_at'] : null,
            notes: $row['notes'] !== null ? (string) $row['notes'] : null,
            evidenceUrl: $row['evidence_url'] !== null ? (string) $row['evidence_url'] : null,
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
