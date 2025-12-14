<?php

declare(strict_types=1);

namespace App\Application\Timesheets;

use DateTimeImmutable;
use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\TimesheetRepositoryInterface;

class SubmitTimesheetUseCase implements UseCase
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
            throw new InvalidArgumentException('Datos inválidos para envío de timesheet.');
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

        if (! in_array($timesheet->status(), ['draft', 'rejected'], true)) {
            return $timesheet->toArray();
        }

        $submittedAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $timesheet = $this->timesheets->update($timesheet->id(), $timesheet->companyId(), [
            'status' => 'submitted',
            'submitted_at' => $submittedAt,
        ]);

        return $timesheet->toArray();
    }
}
