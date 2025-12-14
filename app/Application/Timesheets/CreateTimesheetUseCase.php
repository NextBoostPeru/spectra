<?php

declare(strict_types=1);

namespace App\Application\Timesheets;

use DateTimeImmutable;
use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\TimesheetRepositoryInterface;

class CreateTimesheetUseCase implements UseCase
{
    public function __construct(private readonly TimesheetRepositoryInterface $timesheets)
    {
    }

    /**
     * @param array{company_id:string,assignment_id:string,work_date:string,hours:float,description?:string|null,auto_submit?:bool|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Payload inválido para timesheet.');
        }

        foreach (['company_id', 'assignment_id', 'work_date', 'hours'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $status = ($input['auto_submit'] ?? false) === true ? 'submitted' : 'draft';
        $submittedAt = $status === 'submitted' ? (new DateTimeImmutable())->format('Y-m-d H:i:s') : null;

        $timesheet = $this->timesheets->create([
            'company_id' => (string) $input['company_id'],
            'assignment_id' => (string) $input['assignment_id'],
            'work_date' => (string) $input['work_date'],
            'hours' => (float) $input['hours'],
            'description' => $input['description'] ?? null,
            'status' => $status,
            'submitted_at' => $submittedAt,
        ]);

        return $timesheet->toArray();
    }
}
