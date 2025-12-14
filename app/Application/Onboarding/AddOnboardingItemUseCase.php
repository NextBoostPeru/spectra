<?php

declare(strict_types=1);

namespace App\Application\Onboarding;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\OnboardingChecklistRepositoryInterface;

class AddOnboardingItemUseCase implements UseCase
{
    public function __construct(private readonly OnboardingChecklistRepositoryInterface $checklists)
    {
    }

    public function __invoke(mixed $input): array
    {
        $checklistId = (string) ($input['checklist_id'] ?? '');
        $companyId = (string) ($input['company_id'] ?? '');
        $title = (string) ($input['title'] ?? '');

        if ($checklistId === '' || $title === '') {
            throw new ApplicationException('El item requiere checklist_id y título.');
        }

        $checklist = $this->checklists->find($checklistId, $companyId);
        if ($checklist === null) {
            throw new ApplicationException('Checklist no encontrado para la empresa.');
        }

        $item = $this->checklists->addItem($checklistId, [
            'title' => $title,
            'description' => $input['description'] ?? null,
            'sort_order' => (int) ($input['sort_order'] ?? 1),
            'is_required' => (bool) ($input['is_required'] ?? true),
            'is_access_provision' => (bool) ($input['is_access_provision'] ?? false),
            'system_name' => $input['system_name'] ?? null,
            'resource' => $input['resource'] ?? null,
            'due_days' => $input['due_days'] ?? null,
        ]);

        return $item->toArray();
    }
}
