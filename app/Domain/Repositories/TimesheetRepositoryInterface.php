<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Timesheet;

interface TimesheetRepositoryInterface
{
    public function create(array $data): Timesheet;

    public function findById(string $id, string $companyId): ?Timesheet;

    /**
     * @return list<Timesheet>
     */
    public function listByAssignment(string $assignmentId, string $companyId, int $page, int $pageSize): array;

    public function countByAssignment(string $assignmentId, string $companyId): int;

    public function update(string $id, string $companyId, array $data): Timesheet;
}
