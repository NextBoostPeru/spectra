<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class DeliverableReview
{
    public function __construct(
        private readonly string $id,
        private readonly string $deliverableId,
        private readonly string $reviewerCompanyUserId,
        private readonly string $decision,
        private readonly ?int $score,
        private readonly ?string $comments,
        private readonly string $createdAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'deliverable_id' => $this->deliverableId,
            'reviewer_company_user_id' => $this->reviewerCompanyUserId,
            'decision' => $this->decision,
            'score' => $this->score,
            'comments' => $this->comments,
            'created_at' => $this->createdAt,
        ];
    }
}
