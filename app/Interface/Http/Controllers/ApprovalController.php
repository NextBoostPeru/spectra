<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use InvalidArgumentException;
use App\Application\Approvals\StartApprovalRequestUseCase;
use App\Application\Approvals\ResolveApprovalStepUseCase;
use App\Interface\Http\Requests\RequestValidator;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly StartApprovalRequestUseCase $start,
        private readonly ResolveApprovalStepUseCase $resolve,
        private readonly RequestValidator $validator = new RequestValidator(),
    ) {
    }

    public function start(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'object_type' => static fn ($value): bool => is_string($value) && $value !== '',
                'object_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $payload['created_by_company_user_id'] = $request['created_by_company_user_id'] ?? null;
            $payload['amount'] = isset($request['amount']) ? (float) $request['amount'] : null;
            $payload['currency_id'] = isset($request['currency_id']) ? (int) $request['currency_id'] : null;

            $result = ($this->start)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    public function resolve(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'approval_request_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'action' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $payload['acted_by_company_user_id'] = $request['acted_by_company_user_id'] ?? null;
            $payload['comment'] = $request['comment'] ?? null;
            $payload['step_id'] = $request['step_id'] ?? null;

            $result = ($this->resolve)($payload);

            return $this->ok(['approval_request' => $result]);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
