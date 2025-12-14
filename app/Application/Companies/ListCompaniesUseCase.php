<?php

declare(strict_types=1);

namespace App\Application\Companies;

use App\Application\Contracts\UseCase;
use App\Application\Pagination\PaginationRequest;
use App\Application\Pagination\PaginationResult;
use App\Domain\Repositories\CompanyRepositoryInterface;

class ListCompaniesUseCase implements UseCase
{
    public function __construct(private readonly CompanyRepositoryInterface $companies)
    {
    }

    /**
     * @param array{page?:int,page_size?:int} $input
     */
    public function __invoke(mixed $input): PaginationResult
    {
        $page = isset($input['page']) ? (int) $input['page'] : 1;
        $pageSize = isset($input['page_size']) ? (int) $input['page_size'] : 15;

        $pagination = new PaginationRequest($page, $pageSize);
        $items = $this->companies->paginate($pagination->page, $pagination->pageSize);
        $total = $this->companies->count();

        return new PaginationResult(
            items: array_map(static fn (array $row): array => [
                'id' => $row['id'],
                'legal_name' => $row['legal_name'],
                'trade_name' => $row['trade_name'],
                'country_id' => (int) $row['country_id'],
                'default_currency_id' => (int) $row['default_currency_id'],
                'timezone' => $row['timezone'],
                'status' => $row['status'],
            ], $items),
            total: $total,
            page: $pagination->page,
            pageSize: $pagination->pageSize,
        );
    }
}
