<?php

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\OnboardingChecklist;
use App\Domain\Entities\OnboardingItem;
use App\Domain\Repositories\OnboardingChecklistRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class OnboardingChecklistRepository extends PdoRepository implements OnboardingChecklistRepositoryInterface
{
    public function create(string $companyId, array $data): OnboardingChecklist
    {
        return $this->guard(function () use ($companyId, $data): OnboardingChecklist {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO onboarding_checklists (id, company_id, name, description, category)
VALUES (:id, :company_id, :name, :description, :category)
SQL);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':name', $data['name']);
            $statement->bindValue(':description', $data['description'] ?? null);
            $statement->bindValue(':category', $data['category'] ?? null);
            $statement->execute();

            return $this->find($id, $companyId); // @phpstan-ignore-line
        });
    }

    public function addItem(string $checklistId, array $data): OnboardingItem
    {
        return $this->guard(function () use ($checklistId, $data): OnboardingItem {
            $id = $this->uuid();
            $statement = $this->connection->prepare(<<<'SQL'
INSERT INTO onboarding_items (id, checklist_id, title, description, sort_order, is_required, is_access_provision, system_name, resource, due_days)
VALUES (:id, :checklist_id, :title, :description, :sort_order, :is_required, :is_access_provision, :system_name, :resource, :due_days)
SQL);
            $statement->bindValue(':id', $id);
            $statement->bindValue(':checklist_id', $checklistId);
            $statement->bindValue(':title', $data['title']);
            $statement->bindValue(':description', $data['description'] ?? null);
            $statement->bindValue(':sort_order', $data['sort_order'] ?? 1, \PDO::PARAM_INT);
            $statement->bindValue(':is_required', $data['is_required'] ?? true, \PDO::PARAM_BOOL);
            $statement->bindValue(':is_access_provision', $data['is_access_provision'] ?? false, \PDO::PARAM_BOOL);
            $statement->bindValue(':system_name', $data['system_name'] ?? null);
            $statement->bindValue(':resource', $data['resource'] ?? null);
            $statement->bindValue(':due_days', $data['due_days'] ?? null, \PDO::PARAM_INT);
            $statement->execute();

            return new OnboardingItem(
                id: $id,
                checklistId: $checklistId,
                title: $data['title'],
                description: $data['description'] ?? null,
                sortOrder: (int) ($data['sort_order'] ?? 1),
                isRequired: (bool) ($data['is_required'] ?? true),
                isAccessProvision: (bool) ($data['is_access_provision'] ?? false),
                systemName: $data['system_name'] ?? null,
                resource: $data['resource'] ?? null,
                dueDays: $data['due_days'] ?? null,
            );
        });
    }

    public function find(string $checklistId, string $companyId): ?OnboardingChecklist
    {
        return $this->guard(function () use ($checklistId, $companyId): ?OnboardingChecklist {
            $query = 'SELECT id, company_id, name, description, category FROM onboarding_checklists WHERE id = :id';
            $query = $this->withCompanyScope($query, $companyId, 'onboarding_checklists');
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':id', $checklistId);
            $statement->execute();
            $row = $statement->fetch();
            if ($row === false) {
                return null;
            }

            return new OnboardingChecklist(
                id: (string) $row['id'],
                companyId: (string) $row['company_id'],
                name: (string) $row['name'],
                description: $row['description'] !== null ? (string) $row['description'] : null,
                category: $row['category'] !== null ? (string) $row['category'] : null,
            );
        });
    }

    public function items(string $checklistId, string $companyId): array
    {
        return $this->guard(function () use ($checklistId, $companyId): array {
            $query = 'SELECT oi.id, oi.checklist_id, oi.title, oi.description, oi.sort_order, oi.is_required, oi.is_access_provision, oi.system_name, oi.resource, oi.due_days
                FROM onboarding_items oi
                INNER JOIN onboarding_checklists oc ON oc.id = oi.checklist_id
                WHERE oi.checklist_id = :checklist_id';
            $query = $this->withCompanyScope($query, $companyId, 'oc', 'company_id');
            $query .= ' ORDER BY oi.sort_order ASC';
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':checklist_id', $checklistId);
            $statement->execute();

            $rows = $statement->fetchAll();
            $items = [];
            foreach ($rows as $row) {
                $items[] = new OnboardingItem(
                    id: (string) $row['id'],
                    checklistId: (string) $row['checklist_id'],
                    title: (string) $row['title'],
                    description: $row['description'] !== null ? (string) $row['description'] : null,
                    sortOrder: (int) $row['sort_order'],
                    isRequired: (bool) $row['is_required'],
                    isAccessProvision: (bool) $row['is_access_provision'],
                    systemName: $row['system_name'] !== null ? (string) $row['system_name'] : null,
                    resource: $row['resource'] !== null ? (string) $row['resource'] : null,
                    dueDays: $row['due_days'] !== null ? (int) $row['due_days'] : null,
                );
            }

            return $items;
        });
    }

    public function listByCompany(string $companyId): array
    {
        return $this->guard(function () use ($companyId): array {
            $query = 'SELECT id, company_id, name, description, category FROM onboarding_checklists';
            $query = $this->withCompanyScope($query, $companyId, 'onboarding_checklists');
            $query .= ' ORDER BY name ASC';
            $statement = $this->connection->query($query);

            $rows = $statement?->fetchAll() ?? [];
            $result = [];
            foreach ($rows as $row) {
                $result[] = new OnboardingChecklist(
                    id: (string) $row['id'],
                    companyId: (string) $row['company_id'],
                    name: (string) $row['name'],
                    description: $row['description'] !== null ? (string) $row['description'] : null,
                    category: $row['category'] !== null ? (string) $row['category'] : null,
                );
            }

            return $result;
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
