<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ApprovalRequest;

interface ApprovalRequestRepositoryInterface
{
    public function findByObject(string $objectType, string $objectId, string $companyId): ?ApprovalRequest;

    public function findWithSteps(string $requestId, string $companyId): ?ApprovalRequest;

    public function create(array $data): ApprovalRequest;

    public function updateStatus(string $requestId, string $companyId, string $status): ApprovalRequest;
}
