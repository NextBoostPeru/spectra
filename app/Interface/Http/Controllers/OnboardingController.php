<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use InvalidArgumentException;
use App\Application\Exceptions\ApplicationException;
use App\Application\Onboarding\AddOnboardingItemUseCase;
use App\Application\Onboarding\AssignOnboardingChecklistUseCase;
use App\Application\Onboarding\CompleteOnboardingItemUseCase;
use App\Application\Onboarding\CreateOnboardingChecklistUseCase;
use App\Application\Onboarding\ListOnboardingAssignmentsUseCase;
use App\Interface\Http\Requests\RequestValidator;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly CreateOnboardingChecklistUseCase $createChecklist,
        private readonly AddOnboardingItemUseCase $addItem,
        private readonly AssignOnboardingChecklistUseCase $assign,
        private readonly CompleteOnboardingItemUseCase $completeItem,
        private readonly ListOnboardingAssignmentsUseCase $listAssignments,
        private readonly RequestValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function storeChecklist(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'name' => static fn ($value): bool => is_string($value) && $value !== '',
                'description' => static fn ($value): bool => $value === null || is_string($value),
                'category' => static fn ($value): bool => $value === null || is_string($value),
            ]);

            $result = ($this->createChecklist)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function addItem(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'checklist_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'title' => static fn ($value): bool => is_string($value) && $value !== '',
                'description' => static fn ($value): bool => $value === null || is_string($value),
                'sort_order' => static fn ($value): bool => $value === null || is_numeric($value),
                'is_required' => static fn ($value): bool => $value === null || is_bool($value),
                'is_access_provision' => static fn ($value): bool => $value === null || is_bool($value),
                'system_name' => static fn ($value): bool => $value === null || is_string($value),
                'resource' => static fn ($value): bool => $value === null || is_string($value),
                'due_days' => static fn ($value): bool => $value === null || is_numeric($value),
            ]);

            $result = ($this->addItem)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function assign(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'checklist_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'subject_type' => static fn ($value): bool => is_string($value) && $value !== '',
                'subject_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->assign)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function completeItem(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'assignment_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'assignment_item_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'status' => static fn ($value): bool => $value === null || is_string($value),
                'assignee_company_user_id' => static fn ($value): bool => $value === null || is_string($value),
                'notes' => static fn ($value): bool => $value === null || is_string($value),
                'evidence_url' => static fn ($value): bool => $value === null || is_string($value),
                'system_name' => static fn ($value): bool => $value === null || is_string($value),
                'resource' => static fn ($value): bool => $value === null || is_string($value),
                'access_level' => static fn ($value): bool => $value === null || is_string($value),
                'account_identifier' => static fn ($value): bool => $value === null || is_string($value),
            ]);

            $result = ($this->completeItem)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function listAssignments(array $request): string
    {
        try {
            $page = (int) ($request['page'] ?? 1);
            $pageSize = (int) ($request['page_size'] ?? 15);
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'subject_type' => static fn ($value): bool => is_string($value) && $value !== '',
                'subject_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->listAssignments)([
                'company_id' => $payload['company_id'],
                'subject_type' => $payload['subject_type'],
                'subject_id' => $payload['subject_id'],
                'page' => $page,
                'page_size' => $pageSize,
            ]);

            return $this->ok($result->toArray()['data'], $result->toArray()['meta']);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
