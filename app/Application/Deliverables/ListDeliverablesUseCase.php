<?php

declare(strict_types=1);

namespace App\Application\Deliverables;

use App\Application\Contracts\UseCase;
use App\Application\Pagination\PaginationRequest;
use App\Application\Pagination\PaginationResult;
use App\Domain\Repositories\DeliverableRepositoryInterface;

class ListDeliverablesUseCase implements UseCase
{
    public function __construct(private readonly DeliverableRepositoryInterface $deliverables)
    {
    }

    public function __invoke(mixed $input): PaginationResult
    {
        $companyId = (string) ($input['company_id'] ?? '');
        $projectId = isset($input['project_id']) ? (string) $input['project_id'] : null;
        $page = (int) ($input['page'] ?? 1);
        $pageSize = (int) ($input['page_size'] ?? 15);

        $request = new PaginationRequest($page, $pageSize);
        $data = $this->deliverables->paginate($companyId, $request->page, $request->pageSize, $projectId);
        $total = $this->deliverables->count($companyId, $projectId);

        return new PaginationResult(
            array_map(static fn ($deliverable) => $deliverable->toArray(), $data),
            $request->page,
            $request->pageSize,
            $total,
        );
    }
}
