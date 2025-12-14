<?php

declare(strict_types=1);

namespace App\Application\Timesheets;

use DateTimeImmutable;
use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\TimesheetRepositoryInterface;

class ApproveTimesheetUseCase implements UseCase
{
    public function __construct(private readonly TimesheetRepositoryInterface $timesheets)
    {
    }

    /**
     * @param array{company_id:string,timesheet_id:string,approved_by_company_user_id?:string|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Datos inválidos para aprobación.');
        }

        foreach (['company_id', 'timesheet_id'] as $required) {
            if (empty($input[$required])) {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $timesheet = $this->timesheets->findById((string) $input['timesheet_id'], (string) $input['company_id']);
        if ($timesheet === null) {
            throw new InvalidArgumentException('Timesheet no encontrada.');
        }

        if (! in_array($timesheet->status(), ['submitted', 'rejected'], true)) {
            return $timesheet->toArray();
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $timesheet = $this->timesheets->update($timesheet->id(), $timesheet->companyId(), [
            'status' => 'approved',
            'approved_by_company_user_id' => $input['approved_by_company_user_id'] ?? null,
            'approved_at' => $now,
        ]);

        return $timesheet->toArray();
    }
}
