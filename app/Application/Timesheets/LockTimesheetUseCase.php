<?php

declare(strict_types=1);

namespace App\Application\Timesheets;

use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\TimesheetRepositoryInterface;

class LockTimesheetUseCase implements UseCase
{
    public function __construct(private readonly TimesheetRepositoryInterface $timesheets)
    {
    }

    /**
     * @param array{company_id:string,timesheet_id:string} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Datos inválidos para cierre de timesheet.');
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

        if ($timesheet->status() !== 'approved') {
            return $timesheet->toArray();
        }

        $timesheet = $this->timesheets->update($timesheet->id(), $timesheet->companyId(), [
            'status' => 'locked',
        ]);

        return $timesheet->toArray();
    }
}
