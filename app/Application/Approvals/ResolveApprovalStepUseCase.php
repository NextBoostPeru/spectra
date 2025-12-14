<?php

declare(strict_types=1);

namespace App\Application\Approvals;

use DateTimeImmutable;
use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\ApprovalRequestRepositoryInterface;
use App\Domain\Repositories\ApprovalStepRepositoryInterface;
use App\Domain\Entities\ApprovalStep;

class ResolveApprovalStepUseCase implements UseCase
{
    public function __construct(
        private readonly ApprovalRequestRepositoryInterface $requests,
        private readonly ApprovalStepRepositoryInterface $steps,
    ) {
    }

    /**
     * @param array{company_id:string,approval_request_id:string,action:string,acted_by_company_user_id?:string|null,comment?:string|null,step_id?:string|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Datos inválidos para resolución de aprobación.');
        }

        foreach (['company_id', 'approval_request_id', 'action'] as $required) {
            if (empty($input[$required])) {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $request = $this->requests->findWithSteps((string) $input['approval_request_id'], (string) $input['company_id']);
        if ($request === null) {
            throw new InvalidArgumentException('Solicitud de aprobación no encontrada.');
        }

        $steps = $request->toArray()['steps'];
        if ($steps === []) {
            return $request->toArray();
        }

        $currentStep = $this->resolveCurrentStep($steps, $input['step_id'] ?? null);

        if ($currentStep === null) {
            return $request->toArray();
        }

        $action = strtolower((string) $input['action']);
        if (! in_array($action, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException('Acción no soportada.');
        }

        $status = $action === 'approve' ? 'approved' : 'rejected';
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->steps->update($currentStep->id(), $request->id(), [
            'status' => $status,
            'acted_by_company_user_id' => $input['acted_by_company_user_id'] ?? null,
            'acted_at' => $now,
            'comment' => $input['comment'] ?? null,
        ]);

        $pending = array_filter($this->steps->listByRequest($request->id()), static function (ApprovalStep $step): bool {
            return $step->status() === 'pending';
        });

        $newStatus = $status === 'rejected' ? 'rejected' : ($pending === [] ? 'approved' : $request->status());
        $request = $this->requests->updateStatus($request->id(), (string) $input['company_id'], $newStatus);

        return $this->requests->findWithSteps($request->id(), (string) $input['company_id'])?->toArray() ?? $request->toArray();
    }

    /**
     * @param list<array<string,mixed>> $steps
     */
    private function resolveCurrentStep(array $steps, ?string $stepId): ?ApprovalStep
    {
        $target = null;
        foreach ($steps as $step) {
            if ($stepId !== null && $step['id'] === $stepId) {
                $target = $step;
                break;
            }
            if ($stepId === null && $step['status'] === 'pending') {
                if ($target === null || $step['sequence_order'] < $target['sequence_order']) {
                    $target = $step;
                }
            }
        }

        if ($target === null) {
            return null;
        }

        return new ApprovalStep(
            $target['id'],
            $target['approval_request_id'],
            (int) $target['sequence_order'],
            $target['required_role_id'],
            $target['assigned_to_company_user_id'] ?? null,
            $target['status'],
            $target['acted_by_company_user_id'] ?? null,
            $target['acted_at'] ?? null,
            $target['comment'] ?? null,
        );
    }
}
