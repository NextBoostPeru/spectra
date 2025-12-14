<?php

declare(strict_types=1);

namespace App\Application\Deliverables;

use DateTimeImmutable;
use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\DeliverableRepositoryInterface;

class SubmitDeliverableReviewUseCase implements UseCase
{
    public function __construct(private readonly DeliverableRepositoryInterface $deliverables)
    {
    }

    public function __invoke(mixed $input): array
    {
        $companyId = (string) ($input['company_id'] ?? '');
        $deliverableId = (string) ($input['deliverable_id'] ?? '');
        $decision = (string) ($input['decision'] ?? 'in_review');

        if ($companyId === '' || $deliverableId === '' || $decision === '') {
            throw new ApplicationException('Faltan datos de revisión.');
        }

        $review = $this->deliverables->addReview($deliverableId, $companyId, [
            'reviewer_company_user_id' => $input['reviewer_company_user_id'] ?? '',
            'decision' => $decision,
            'score' => $input['score'] ?? null,
            'comments' => $input['comments'] ?? null,
        ]);

        $deliverable = $this->deliverables->find($deliverableId, $companyId);
        if ($deliverable === null) {
            throw new ApplicationException('Entregable no encontrado.');
        }

        $status = $decision === 'approved'
            ? 'accepted'
            : ($decision === 'rejected' ? 'rejected' : 'in_review');

        $this->deliverables->updateStatus(
            $deliverableId,
            $companyId,
            $status,
            $deliverable->toArray()['submitted_at'] ?? (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            (new DateTimeImmutable())->format('Y-m-d H:i:s')
        );

        return [
            'deliverable' => $this->deliverables->find($deliverableId, $companyId)?->toArray(),
            'review' => $review->toArray(),
        ];
    }
}
