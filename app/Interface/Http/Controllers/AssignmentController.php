<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Application\Assignments\CreateAssignmentUseCase;
use App\Application\Assignments\ListAssignmentsByProjectUseCase;
use App\Application\Exceptions\ApplicationException;
use App\Interface\Http\Requests\RequestValidator;
use InvalidArgumentException;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly CreateAssignmentUseCase $createAssignment,
        private readonly ListAssignmentsByProjectUseCase $listAssignments,
        private readonly RequestValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function store(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'project_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'freelancer_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'role_title' => static fn ($value): bool => is_string($value) && $value !== '',
                'start_date' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $assignment = ($this->createAssignment)($payload);

            return $this->created(['assignment' => $assignment]);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function index(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'project_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $assignments = ($this->listAssignments)($payload);

            return $this->ok(['assignments' => $assignments]);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
