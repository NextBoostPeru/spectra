<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ApprovalPolicy;

interface ApprovalPolicyRepositoryInterface
{
    /**
     * @return list<ApprovalPolicy>
     */
    public function activePolicies(string $companyId, string $objectType): array;
}
