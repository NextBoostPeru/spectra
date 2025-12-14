<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\OnboardingChecklist;
use App\Domain\Entities\OnboardingItem;

interface OnboardingChecklistRepositoryInterface
{
    public function create(string $companyId, array $data): OnboardingChecklist;

    public function addItem(string $checklistId, array $data): OnboardingItem;

    public function find(string $checklistId, string $companyId): ?OnboardingChecklist;

    /**
     * @return OnboardingItem[]
     */
    public function items(string $checklistId, string $companyId): array;

    /**
     * @return OnboardingChecklist[]
     */
    public function listByCompany(string $companyId): array;
}
