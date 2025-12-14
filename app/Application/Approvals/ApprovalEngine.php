<?php

declare(strict_types=1);

namespace App\Application\Approvals;

use App\Domain\Repositories\ApprovalPolicyRepositoryInterface;
use App\Domain\Repositories\ApprovalRequestRepositoryInterface;
use App\Domain\Repositories\ApprovalStepRepositoryInterface;
use App\Domain\Entities\ApprovalPolicy;
use App\Domain\Entities\ApprovalRule;
use App\Domain\Entities\ApprovalRequest;

class ApprovalEngine
{
    public function __construct(
        private readonly ApprovalPolicyRepositoryInterface $policies,
        private readonly ApprovalRequestRepositoryInterface $requests,
        private readonly ApprovalStepRepositoryInterface $steps,
    ) {
    }

    /**
     * @return array{request:ApprovalRequest,steps:array<int,array<string,mixed>>}
     */
    public function start(string $companyId, string $objectType, string $objectId, ?string $createdByCompanyUserId = null, ?float $amount = null, ?int $currencyId = null): array
    {
        $existing = $this->requests->findByObject($objectType, $objectId, $companyId);
        if ($existing !== null) {
            return [
                'request' => $existing,
                'steps' => array_map(static fn ($step) => $step->toArray(), $this->steps->listByRequest($existing->id())),
            ];
        }

        $policy = $this->pickPolicy($companyId, $objectType, $amount, $currencyId);

        $status = $policy === null ? 'approved' : 'pending';
        $request = $this->requests->create([
            'company_id' => $companyId,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'status' => $status,
            'created_by_company_user_id' => $createdByCompanyUserId,
        ]);

        $steps = [];
        if ($policy !== null) {
            foreach ($policy->toArray()['rules'] as $rule) {
                $steps[] = [
                    'id' => $this->uuid(),
                    'approval_request_id' => $request->id(),
                    'sequence_order' => $rule['sequence_order'],
                    'required_role_id' => $rule['required_role_id'],
                    'assigned_to_company_user_id' => null,
                    'status' => 'pending',
                    'acted_by_company_user_id' => null,
                    'acted_at' => null,
                    'comment' => null,
                ];
            }
        }

        if ($steps !== []) {
            $this->steps->bulkCreate($steps);
        }

        return [
            'request' => $this->requests->findWithSteps($request->id(), $companyId) ?? $request,
            'steps' => $steps,
        ];
    }

    private function pickPolicy(string $companyId, string $objectType, ?float $amount, ?int $currencyId): ?ApprovalPolicy
    {
        $policies = $this->policies->activePolicies($companyId, $objectType);

        foreach ($policies as $policy) {
            $matchedRules = array_filter($policy->toArray()['rules'], static function (array $rule) use ($amount, $currencyId): bool {
                $ruleObject = new ApprovalRule(
                    $rule['id'],
                    $rule['policy_id'],
                    $rule['min_amount'],
                    $rule['max_amount'],
                    $rule['currency_id'],
                    $rule['condition'],
                    $rule['sequence_order'],
                    $rule['required_role_id'],
                );

                return $ruleObject->matchesAmount($amount, $currencyId);
            });

            if ($matchedRules !== []) {
                return new ApprovalPolicy(
                    $policy->toArray()['id'],
                    $policy->toArray()['company_id'],
                    $policy->toArray()['name'],
                    $policy->toArray()['object_type'],
                    (bool) $policy->toArray()['is_active'],
                    array_map(static fn (array $rule) => new ApprovalRule(
                        $rule['id'],
                        $rule['policy_id'],
                        $rule['min_amount'],
                        $rule['max_amount'],
                        $rule['currency_id'],
                        $rule['condition'],
                        $rule['sequence_order'],
                        $rule['required_role_id'],
                    ), $matchedRules),
                );
            }
        }

        return null;
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
