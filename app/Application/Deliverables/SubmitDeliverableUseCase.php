<?php

declare(strict_types=1);

namespace App\Application\Deliverables;

use DateTimeImmutable;
use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\DeliverableRepositoryInterface;

class SubmitDeliverableUseCase implements UseCase
{
    public function __construct(private readonly DeliverableRepositoryInterface $deliverables)
    {
    }

    public function __invoke(mixed $input): array
    {
        $companyId = (string) ($input['company_id'] ?? '');
        $deliverableId = (string) ($input['deliverable_id'] ?? '');

        if ($companyId === '' || $deliverableId === '') {
            throw new ApplicationException('Faltan datos para enviar el entregable.');
        }

        $submittedAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $deliverable = $this->deliverables->updateStatus($deliverableId, $companyId, 'submitted', $submittedAt, null);

        if ($deliverable === null) {
            throw new ApplicationException('No se pudo actualizar el entregable.');
        }

        return $deliverable->toArray();
    }
}
