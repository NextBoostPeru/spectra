<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\OnboardingAssignment;
use App\Domain\Entities\OnboardingAssignmentItem;

interface OnboardingAssignmentRepositoryInterface
{
    public function create(array $data): OnboardingAssignment;

    /**
     * @param array{item_id: string, due_days?: int|null}[] $items
     */
    public function createItems(string $assignmentId, array $items, ?string $startDate = null): void;

    public function updateAssignmentStatus(string $assignmentId, string $companyId, string $status, ?string $completedAt = null): void;

    public function updateItem(string $assignmentItemId, string $companyId, array $data): ?OnboardingAssignmentItem;

    /**
     * @return array{assignment: OnboardingAssignment, items: OnboardingAssignmentItem[]}|null
     */
    public function findWithItems(string $assignmentId, string $companyId): ?array;

    /**
     * @return OnboardingAssignment[]
     */
    public function listForSubject(string $companyId, string $subjectType, string $subjectId): array;
}
