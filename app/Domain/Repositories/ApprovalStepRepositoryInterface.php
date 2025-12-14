<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ApprovalStep;

interface ApprovalStepRepositoryInterface
{
    /**
     * @param list<array{id:string,approval_request_id:string,sequence_order:int,required_role_id:string,assigned_to_company_user_id?:string|null,status:string,acted_by_company_user_id?:string|null,acted_at?:string|null,comment?:string|null}> $steps
     */
    public function bulkCreate(array $steps): void;

    /**
     * @return list<ApprovalStep>
     */
    public function listByRequest(string $requestId): array;

    public function update(string $stepId, string $requestId, array $data): ApprovalStep;
}
