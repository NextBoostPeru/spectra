<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use InvalidArgumentException;
use App\Application\Deliverables\CreateDeliverableUseCase;
use App\Application\Deliverables\ListDeliverablesUseCase;
use App\Application\Deliverables\RecordNpsResponseUseCase;
use App\Application\Deliverables\SubmitDeliverableReviewUseCase;
use App\Application\Deliverables\SubmitDeliverableUseCase;
use App\Application\Exceptions\ApplicationException;
use App\Interface\Http\Requests\RequestValidator;

class DeliverableController extends Controller
{
    public function __construct(
        private readonly CreateDeliverableUseCase $create,
        private readonly SubmitDeliverableUseCase $submit,
        private readonly SubmitDeliverableReviewUseCase $review,
        private readonly ListDeliverablesUseCase $list,
        private readonly RecordNpsResponseUseCase $recordNps,
        private readonly RequestValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function index(array $request): string
    {
        try {
            $page = (int) ($request['page'] ?? 1);
            $pageSize = (int) ($request['page_size'] ?? 15);
            $projectId = $request['project_id'] ?? null;
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'project_id' => static fn ($value): bool => $value === null || is_string($value),
            ]);

            $result = ($this->list)([
                'company_id' => $payload['company_id'],
                'project_id' => $projectId,
                'page' => $page,
                'page_size' => $pageSize,
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
                'project_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'assignment_id' => static fn ($value): bool => $value === null || is_string($value),
                'title' => static fn ($value): bool => is_string($value) && $value !== '',
                'description' => static fn ($value): bool => $value === null || is_string($value),
                'due_date' => static fn ($value): bool => $value === null || is_string($value),
            ]);

            $result = ($this->create)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function submit(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'deliverable_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->submit)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function review(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'deliverable_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'reviewer_company_user_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'decision' => static fn ($value): bool => is_string($value) && $value !== '',
                'score' => static fn ($value): bool => $value === null || is_numeric($value),
                'comments' => static fn ($value): bool => $value === null || is_string($value),
            ]);

            $result = ($this->review)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function recordNps(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'project_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'respondent_company_user_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'score' => static fn ($value): bool => is_numeric($value),
                'comment' => static fn ($value): bool => $value === null || is_string($value),
            ]);

            $result = ($this->recordNps)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
