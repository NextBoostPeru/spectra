<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Application\Pagination\PaginationResult;
use App\Domain\Repositories\ContractTemplateRepositoryInterface;

class ListContractTemplatesUseCase implements UseCase
{
    public function __construct(private readonly ContractTemplateRepositoryInterface $templates)
    {
    }

    /**
     * @param array{company_id:string,page?:int|null,page_size?:int|null} $input
     */
    public function __invoke(mixed $input): PaginationResult
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Filtro inválido para templates.');
        }

        if (empty($input['company_id'])) {
            throw new InvalidArgumentException('company_id es requerido');
        }

        $page = isset($input['page']) ? (int) $input['page'] : 1;
        $pageSize = isset($input['page_size']) ? (int) $input['page_size'] : 15;

        $items = $this->templates->list((string) $input['company_id'], $page, $pageSize);

        return new PaginationResult(
            data: array_map(static fn ($template): array => $template->toArray(), $items),
            page: $page,
            pageSize: $pageSize,
            total: count($items),
        );
    }
}
