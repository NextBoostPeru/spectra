<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class OnboardingItem
{
    public function __construct(
        private readonly string $id,
        private readonly string $checklistId,
        private readonly string $title,
        private readonly ?string $description,
        private readonly int $sortOrder,
        private readonly bool $isRequired,
        private readonly bool $isAccessProvision,
        private readonly ?string $systemName = null,
        private readonly ?string $resource = null,
        private readonly ?int $dueDays = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'checklist_id' => $this->checklistId,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => $this->sortOrder,
            'is_required' => $this->isRequired,
            'is_access_provision' => $this->isAccessProvision,
            'system_name' => $this->systemName,
            'resource' => $this->resource,
            'due_days' => $this->dueDays,
        ];
    }
}
