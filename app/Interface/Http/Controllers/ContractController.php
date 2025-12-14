<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Application\Contracts\AddContractVersionUseCase;
use App\Application\Contracts\ApproveContractUseCase;
use App\Application\Contracts\CreateContractTemplateUseCase;
use App\Application\Contracts\CreateContractUseCase;
use App\Application\Contracts\ListContractTemplatesUseCase;
use App\Application\Contracts\NotifyExpiringContractsJob;
use App\Application\Contracts\SendContractForSignatureUseCase;
use App\Application\Exceptions\ApplicationException;
use App\Interface\Http\Requests\RequestValidator;
use InvalidArgumentException;

class ContractController extends Controller
{
    public function __construct(
        private readonly ListContractTemplatesUseCase $listTemplates,
        private readonly CreateContractTemplateUseCase $createTemplate,
        private readonly CreateContractUseCase $createContract,
        private readonly AddContractVersionUseCase $addVersion,
        private readonly SendContractForSignatureUseCase $sendForSignature,
        private readonly ApproveContractUseCase $approve,
        private readonly NotifyExpiringContractsJob $notifyExpiring,
        private readonly RequestValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function listTemplates(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'page' => static fn ($value): bool => $value === null || is_numeric($value),
                'page_size' => static fn ($value): bool => $value === null || is_numeric($value),
            ]);

            $result = ($this->listTemplates)([
                'company_id' => $payload['company_id'],
                'page' => $payload['page'] ?? $request['page'] ?? 1,
                'page_size' => $payload['page_size'] ?? $request['page_size'] ?? 15,
            ]);

            $data = $result->toArray();

            return $this->ok($data['data'], $data['meta']);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function storeTemplate(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'type' => static fn ($value): bool => is_string($value) && $value !== '',
                'country_id' => static fn ($value): bool => is_numeric($value),
                'title' => static fn ($value): bool => is_string($value) && $value !== '',
                'body' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $template = ($this->createTemplate)($payload);

            return $this->created($template);
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
                'freelancer_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'template_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'jurisdiction_country_id' => static fn ($value): bool => is_numeric($value),
                'payment_type' => static fn ($value): bool => is_string($value) && $value !== '',
                'rate_currency_id' => static fn ($value): bool => is_numeric($value),
            ]);

            $result = ($this->createContract)(array_merge($request, $payload));

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function addVersion(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'contract_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'body' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->addVersion)($payload);

            return $this->created($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function sendForSignature(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'contract_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'signers' => static fn ($value): bool => is_array($value) && $value !== [],
            ]);

            $result = ($this->sendForSignature)(array_merge($request, $payload));

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function approve(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'contract_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'status' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->approve)(array_merge($request, $payload));

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function notifyExpiring(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'days' => static fn ($value): bool => $value === null || is_numeric($value),
            ]);

            $result = ($this->notifyExpiring)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
