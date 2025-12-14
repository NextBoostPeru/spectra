<?php

declare(strict_types=1);

namespace App\Application\Onboarding;

use DateTimeImmutable;
use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\OnboardingAssignmentRepositoryInterface;
use App\Domain\Repositories\OnboardingChecklistRepositoryInterface;

class AssignOnboardingChecklistUseCase implements UseCase
{
    public function __construct(
        private readonly OnboardingChecklistRepositoryInterface $checklists,
        private readonly OnboardingAssignmentRepositoryInterface $assignments,
    ) {
    }

    public function __invoke(mixed $input): array
    {
        $companyId = (string) ($input['company_id'] ?? '');
        $checklistId = (string) ($input['checklist_id'] ?? '');
        $subjectType = (string) ($input['subject_type'] ?? '');
        $subjectId = (string) ($input['subject_id'] ?? '');

        if ($companyId === '' || $checklistId === '' || $subjectType === '' || $subjectId === '') {
            throw new ApplicationException('Faltan datos para asignar checklist.');
        }

        $checklist = $this->checklists->find($checklistId, $companyId);
        if ($checklist === null) {
            throw new ApplicationException('Checklist no pertenece a la empresa.');
        }

        $start = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $assignment = $this->assignments->create([
            'company_id' => $companyId,
            'checklist_id' => $checklistId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'status' => 'in_progress',
            'started_at' => $start,
        ]);

        $items = $this->checklists->items($checklistId, $companyId);
        $itemsPayload = array_map(static fn ($item): array => [
            'item_id' => $item->toArray()['id'],
            'due_days' => $item->toArray()['due_days'] ?? null,
        ], $items);

        $this->assignments->createItems($assignment->toArray()['id'], $itemsPayload, $start);

        $withItems = $this->assignments->findWithItems($assignment->toArray()['id'], $companyId);

        return [
            'assignment' => $assignment->toArray(),
            'items' => array_map(static fn ($item) => $item->toArray(), $withItems['items'] ?? []),
        ];
    }
}
