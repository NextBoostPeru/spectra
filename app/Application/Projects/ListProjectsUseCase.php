<?php

declare(strict_types=1);

namespace App\Application\Projects;

use App\Application\Contracts\UseCase;
use App\Application\Pagination\PaginationRequest;
use App\Application\Pagination\PaginationResult;
use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Domain\Repositories\ProjectMemberRepositoryInterface;

class ListProjectsUseCase implements UseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $projectMembers,
    ) {
    }

    /**
     * @param array{company_id:string,page?:int,page_size?:int} $input
     */
    public function __invoke(mixed $input): PaginationResult
    {
        $companyId = (string) ($input['company_id'] ?? '');
        if ($companyId === '') {
            throw new \InvalidArgumentException('company_id es requerido');
        }

        $page = isset($input['page']) ? (int) $input['page'] : 1;
        $pageSize = isset($input['page_size']) ? (int) $input['page_size'] : 15;
        $pagination = new PaginationRequest($page, $pageSize);

        $items = $this->projects->paginate($companyId, $pagination->page, $pagination->pageSize);
        $total = $this->projects->count($companyId);

        $data = [];
        foreach ($items as $row) {
            $members = $this->projectMembers->listByProject((string) $row['id'], $companyId);
            $data[] = [
                'id' => $row['id'],
                'company_id' => $row['company_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'country_id' => (int) $row['country_id'],
                'currency_id' => (int) $row['currency_id'],
                'status' => $row['status'],
                'created_by_company_user_id' => $row['created_by_company_user_id'],
                'members' => array_map(static fn ($member) => $member->toArray(), $members),
            ];
        }

        return new PaginationResult(
            items: $data,
            total: $total,
            page: $pagination->page,
            pageSize: $pagination->pageSize,
        );
    }
}
