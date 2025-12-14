<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use InvalidArgumentException;
use App\Application\Timesheets\CreateTimesheetUseCase;
use App\Application\Timesheets\SubmitTimesheetUseCase;
use App\Application\Timesheets\ApproveTimesheetUseCase;
use App\Application\Timesheets\RejectTimesheetUseCase;
use App\Application\Timesheets\LockTimesheetUseCase;
use App\Application\Timesheets\ListTimesheetsUseCase;
use App\Interface\Http\Requests\RequestValidator;

class TimesheetController extends Controller
{
    public function __construct(
        private readonly CreateTimesheetUseCase $create,
        private readonly SubmitTimesheetUseCase $submit,
        private readonly ApproveTimesheetUseCase $approve,
        private readonly RejectTimesheetUseCase $reject,
        private readonly LockTimesheetUseCase $lock,
        private readonly ListTimesheetsUseCase $list,
        private readonly RequestValidator $validator = new RequestValidator(),
    ) {
    }

    public function store(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'assignment_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'work_date' => static fn ($value): bool => is_string($value) && $value !== '',
                'hours' => static fn ($value): bool => is_numeric($value),
            ]);

            $payload['description'] = $request['description'] ?? null;
            $payload['auto_submit'] = $request['auto_submit'] ?? false;

            $timesheet = ($this->create)($payload);

            return $this->created(['timesheet' => $timesheet]);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    public function submit(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'timesheet_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $timesheet = ($this->submit)($payload);

            return $this->ok(['timesheet' => $timesheet]);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    public function approve(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'timesheet_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);
            $payload['approved_by_company_user_id'] = $request['approved_by_company_user_id'] ?? null;

            $timesheet = ($this->approve)($payload);

            return $this->ok(['timesheet' => $timesheet]);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    public function reject(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'timesheet_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);
            $payload['approved_by_company_user_id'] = $request['approved_by_company_user_id'] ?? null;

            $timesheet = ($this->reject)($payload);

            return $this->ok(['timesheet' => $timesheet]);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    public function lock(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'timesheet_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $timesheet = ($this->lock)($payload);

            return $this->ok(['timesheet' => $timesheet]);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    public function index(array $request): string
    {
        try {
            $filters = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'assignment_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $filters['page'] = isset($request['page']) ? (int) $request['page'] : 1;
            $filters['page_size'] = isset($request['page_size']) ? (int) $request['page_size'] : 20;

            $pagination = ($this->list)($filters);

            return $this->ok($pagination);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
