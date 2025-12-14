<?php

declare(strict_types=1);

namespace App\Application\Pagination;

/**
 * Resultado canónico para respuestas paginadas.
 *
 * @template TItem
 */
final class PaginationResult
{
    /**
     * @param list<TItem> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $pageSize
    ) {
    }

    /**
     * @return array{data:list<TItem>,meta:array<string,int|bool>}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->items,
            'meta' => [
                'total' => $this->total,
                'page' => $this->page,
                'page_size' => $this->pageSize,
                'has_next' => $this->total > $this->page * max(1, $this->pageSize),
            ],
        ];
    }
}
