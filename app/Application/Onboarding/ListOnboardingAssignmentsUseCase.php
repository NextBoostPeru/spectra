<?php

declare(strict_types=1);

namespace App\Application\Onboarding;

use App\Application\Contracts\UseCase;
use App\Application\Pagination\PaginationRequest;
use App\Application\Pagination\PaginationResult;
use App\Domain\Repositories\OnboardingAssignmentRepositoryInterface;

class ListOnboardingAssignmentsUseCase implements UseCase
{
    public function __construct(private readonly OnboardingAssignmentRepositoryInterface $assignments)
    {
    }

    public function __invoke(mixed $input): PaginationResult
    {
        $companyId = (string) ($input['company_id'] ?? '');
        $subjectType = (string) ($input['subject_type'] ?? '');
        $subjectId = (string) ($input['subject_id'] ?? '');
        $page = (int) ($input['page'] ?? 1);
        $pageSize = (int) ($input['page_size'] ?? 15);

        $request = new PaginationRequest($page, $pageSize);
        $records = $this->assignments->listForSubject($companyId, $subjectType, $subjectId);
        $total = count($records);
        $chunk = array_slice($records, $request->offset(), $request->pageSize);

        $data = array_map(static fn ($assignment) => $assignment->toArray(), $chunk);

        return new PaginationResult($data, $request->page, $request->pageSize, $total);
    }
}
