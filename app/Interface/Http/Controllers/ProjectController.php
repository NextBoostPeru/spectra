<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Application\Exceptions\ApplicationException;
use App\Application\Projects\AddProjectMemberUseCase;
use App\Application\Projects\CreateProjectUseCase;
use App\Application\Projects\ListProjectsUseCase;
use App\Application\Projects\UpdateProjectUseCase;
use App\Interface\Http\Requests\RequestValidator;
use InvalidArgumentException;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ListProjectsUseCase $listProjects,
        private readonly CreateProjectUseCase $createProject,
        private readonly UpdateProjectUseCase $updateProject,
        private readonly AddProjectMemberUseCase $addMember,
        private readonly RequestValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function index(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'page' => static fn ($value): bool => $value === null || is_numeric($value),
                'page_size' => static fn ($value): bool => $value === null || is_numeric($value),
            ]);

            $result = ($this->listProjects)([
                'company_id' => $payload['company_id'],
                'page' => $payload['page'] ?? $request['page'] ?? 1,
                'page_size' => $payload['page_size'] ?? $request['page_size'] ?? 15,
            ]);

            $payload = $result->toArray();

            return $this->ok($payload['data'], $payload['meta']);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function store(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'name' => static fn ($value): bool => is_string($value) && $value !== '',
                'country_id' => static fn ($value): bool => is_numeric($value),
                'currency_id' => static fn ($value): bool => is_numeric($value),
                'members' => static fn ($value): bool => $value === null || is_array($value),
            ]);

            $result = ($this->createProject)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function update(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'project_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->updateProject)(array_merge($request, $payload));

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function addMember(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'project_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'company_user_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $member = ($this->addMember)($payload);

            return $this->created(['member' => $member]);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
