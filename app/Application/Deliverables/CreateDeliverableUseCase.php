<?php

declare(strict_types=1);

namespace App\Application\Deliverables;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\DeliverableRepositoryInterface;

class CreateDeliverableUseCase implements UseCase
{
    public function __construct(private readonly DeliverableRepositoryInterface $deliverables)
    {
    }

    public function __invoke(mixed $input): array
    {
        $companyId = (string) ($input['company_id'] ?? '');
        $projectId = (string) ($input['project_id'] ?? '');
        $title = (string) ($input['title'] ?? '');

        if ($companyId === '' || $projectId === '' || $title === '') {
            throw new ApplicationException('El entregable requiere company_id, project_id y título.');
        }

        $deliverable = $this->deliverables->create([
            'company_id' => $companyId,
            'project_id' => $projectId,
            'assignment_id' => $input['assignment_id'] ?? null,
            'title' => $title,
            'description' => $input['description'] ?? null,
            'status' => 'pending',
            'due_date' => $input['due_date'] ?? null,
        ]);

        return $deliverable->toArray();
    }
}
