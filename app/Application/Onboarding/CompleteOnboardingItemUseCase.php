<?php

declare(strict_types=1);

namespace App\Application\Onboarding;

use DateTimeImmutable;
use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\AccessProvisionRepositoryInterface;
use App\Domain\Repositories\OnboardingAssignmentRepositoryInterface;
use App\Domain\Repositories\OnboardingChecklistRepositoryInterface;

class CompleteOnboardingItemUseCase implements UseCase
{
    public function __construct(
        private readonly OnboardingAssignmentRepositoryInterface $assignments,
        private readonly OnboardingChecklistRepositoryInterface $checklists,
        private readonly AccessProvisionRepositoryInterface $provisions,
    ) {
    }

    public function __invoke(mixed $input): array
    {
        $assignmentId = (string) ($input['assignment_id'] ?? '');
        $companyId = (string) ($input['company_id'] ?? '');
        $assignmentItemId = (string) ($input['assignment_item_id'] ?? '');
        $status = (string) ($input['status'] ?? 'completed');

        if ($assignmentId === '' || $companyId === '' || $assignmentItemId === '') {
            throw new ApplicationException('Faltan datos para completar el ítem.');
        }

        $assignment = $this->assignments->findWithItems($assignmentId, $companyId);
        if ($assignment === null) {
            throw new ApplicationException('Asignación no encontrada.');
        }

        $item = null;
        foreach ($assignment['items'] as $assignmentItem) {
            if ($assignmentItem->toArray()['id'] === $assignmentItemId) {
                $item = $assignmentItem->toArray();
            }
        }

        if ($item === null) {
            throw new ApplicationException('Ítem no pertenece a la asignación.');
        }

        $checklistItems = $this->checklists->items($assignment['assignment']->toArray()['checklist_id'], $companyId);
        $isAccessProvision = false;
        foreach ($checklistItems as $checklistItem) {
            if ($checklistItem->toArray()['id'] === $item['item_id']) {
                $isAccessProvision = (bool) $checklistItem->toArray()['is_access_provision'];
                break;
            }
        }

        $completedAt = in_array($status, ['completed', 'skipped'], true)
            ? (new DateTimeImmutable())->format('Y-m-d H:i:s')
            : null;

        $updated = $this->assignments->updateItem($assignmentItemId, $companyId, [
            'status' => $status,
            'assignee_company_user_id' => $input['assignee_company_user_id'] ?? null,
            'completed_at' => $completedAt,
            'notes' => $input['notes'] ?? null,
            'evidence_url' => $input['evidence_url'] ?? null,
        ]);

        if ($updated === null) {
            throw new ApplicationException('No fue posible actualizar el ítem.');
        }

        if ($isAccessProvision === true && $status === 'completed') {
            $this->provisions->create([
                'assignment_item_id' => $assignmentItemId,
                'system_name' => (string) ($input['system_name'] ?? ''),
                'resource' => $input['resource'] ?? null,
                'access_level' => $input['access_level'] ?? null,
                'account_identifier' => $input['account_identifier'] ?? null,
                'granted_by_company_user_id' => $input['assignee_company_user_id'] ?? null,
                'granted_at' => $completedAt,
                'notes' => $input['notes'] ?? null,
            ]);
        }

        $refreshed = $this->assignments->findWithItems($assignmentId, $companyId);
        if ($refreshed === null) {
            throw new ApplicationException('No se pudo refrescar la asignación.');
        }

        $pending = array_filter($refreshed['items'], static function ($item): bool {
            return ! in_array($item->toArray()['status'], ['completed', 'skipped'], true);
        });

        if ($pending === []) {
            $this->assignments->updateAssignmentStatus($assignmentId, $companyId, 'completed', $completedAt);
        }

        return [
            'assignment' => $refreshed['assignment']->toArray(),
            'items' => array_map(static fn ($item) => $item->toArray(), $refreshed['items']),
            'provisions' => array_map(static fn ($provision) => $provision->toArray(), $this->provisions->listByAssignmentItem($assignmentItemId)),
        ];
    }
}
