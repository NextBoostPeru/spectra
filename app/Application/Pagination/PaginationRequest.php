<?php

declare(strict_types=1);

namespace App\Application\Pagination;

/**
 * Define los parámetros estándar de paginación para todos los controladores y casos de uso.
 */
final class PaginationRequest
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $pageSize = 15,
        public readonly ?string $sortBy = null,
        public readonly string $direction = 'asc'
    ) {
    }

    public function offset(): int
    {
        return max(0, ($this->page - 1) * $this->pageSize);
    }
}
