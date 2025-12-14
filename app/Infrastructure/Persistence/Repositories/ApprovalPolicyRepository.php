<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Entities\ApprovalPolicy;
use App\Domain\Entities\ApprovalRule;
use App\Domain\Repositories\ApprovalPolicyRepositoryInterface;
use App\Infrastructure\Persistence\PdoRepository;

class ApprovalPolicyRepository extends PdoRepository implements ApprovalPolicyRepositoryInterface
{
    public function activePolicies(string $companyId, string $objectType): array
    {
        return $this->guard(function () use ($companyId, $objectType) {
            $statement = $this->connection->prepare('SELECT id, company_id, name, object_type, is_active FROM approval_policies WHERE company_id = :company_id AND object_type = :object_type AND is_active = 1 ORDER BY created_at DESC');
            $statement->bindValue(':company_id', $companyId);
            $statement->bindValue(':object_type', $objectType);
            $statement->execute();
            $policies = $statement->fetchAll();

            if ($policies === false || $policies === []) {
                return [];
            }

            $policyIds = array_map(static fn ($row) => $row['id'], $policies);
            $rules = $this->loadRules($policyIds);

            return array_map(function ($row) use ($rules) {
                $policyRules = $rules[$row['id']] ?? [];

                return new ApprovalPolicy(
                    id: (string) $row['id'],
                    companyId: (string) $row['company_id'],
                    name: (string) $row['name'],
                    objectType: (string) $row['object_type'],
                    isActive: (bool) $row['is_active'],
                    rules: $policyRules,
                );
            }, $policies);
        });
    }

    private function loadRules(array $policyIds): array
    {
        $statement = $this->connection->prepare('SELECT id, policy_id, min_amount, max_amount, currency_id, condition_json, sequence_order, required_role_id FROM approval_rules WHERE policy_id IN (' . implode(',', array_fill(0, count($policyIds), '?')) . ') ORDER BY sequence_order ASC');
        foreach ($policyIds as $index => $policyId) {
            $statement->bindValue($index + 1, $policyId);
        }

        $statement->execute();
        $rows = $statement->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $policyId = $row['policy_id'];
            $grouped[$policyId] ??= [];
            $grouped[$policyId][] = new ApprovalRule(
                id: (string) $row['id'],
                policyId: (string) $row['policy_id'],
                minAmount: $row['min_amount'] !== null ? (float) $row['min_amount'] : null,
                maxAmount: $row['max_amount'] !== null ? (float) $row['max_amount'] : null,
                currencyId: $row['currency_id'] !== null ? (int) $row['currency_id'] : null,
                condition: $row['condition_json'] !== null ? json_decode((string) $row['condition_json'], true, 512, JSON_THROW_ON_ERROR) : null,
                sequenceOrder: (int) $row['sequence_order'],
                requiredRoleId: (string) $row['required_role_id'],
            );
        }

        return $grouped;
    }
}
