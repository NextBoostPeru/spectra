<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Deliverable;
use App\Domain\Entities\DeliverableReview;

interface DeliverableRepositoryInterface
{
    public function create(array $data): Deliverable;

    /**
     * @return Deliverable[]
     */
    public function paginate(string $companyId, int $page, int $pageSize, ?string $projectId = null): array;

    public function count(string $companyId, ?string $projectId = null): int;

    public function updateStatus(string $deliverableId, string $companyId, string $status, ?string $submittedAt = null, ?string $reviewedAt = null): ?Deliverable;

    public function find(string $deliverableId, string $companyId): ?Deliverable;

    public function addReview(string $deliverableId, string $companyId, array $data): DeliverableReview;
}
