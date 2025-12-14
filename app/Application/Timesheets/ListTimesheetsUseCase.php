<?php

declare(strict_types=1);

namespace App\Application\Timesheets;

use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Application\Pagination\PaginationRequest;
use App\Application\Pagination\PaginationResult;
use App\Domain\Repositories\TimesheetRepositoryInterface;

class ListTimesheetsUseCase implements UseCase
{
    public function __construct(private readonly TimesheetRepositoryInterface $timesheets)
    {
    }

    /**
     * @param array{company_id:string,assignment_id:string,page?:int|null,page_size?:int|null} $input
     */
    public function __invoke(mixed $input): PaginationResult
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Datos inválidos para listar timesheets.');
        }

        foreach (['company_id', 'assignment_id'] as $required) {
            if (empty($input[$required])) {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $pagination = new PaginationRequest($input['page'] ?? 1, $input['page_size'] ?? 20);
        $rows = $this->timesheets->listByAssignment((string) $input['assignment_id'], (string) $input['company_id'], $pagination->page(), $pagination->pageSize());
        $total = $this->timesheets->countByAssignment((string) $input['assignment_id'], (string) $input['company_id']);

        return $pagination->toResult($total, array_map(static fn ($row) => $row->toArray(), $rows));
    }
}
