<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Application\Companies\AddCompanyContactUseCase;
use App\Application\Companies\CreateCompanyUseCase;
use App\Application\Companies\DeleteCompanyContactUseCase;
use App\Application\Companies\DeleteCompanyUseCase;
use App\Application\Companies\ListCompaniesUseCase;
use App\Application\Companies\UpdateCompanySettingsUseCase;
use App\Application\Companies\UpdateCompanyUseCase;
use App\Application\Exceptions\ApplicationException;
use App\Interface\Http\Requests\RequestValidator;
use InvalidArgumentException;

class CompanyController extends Controller
{
    public function __construct(
        private readonly ListCompaniesUseCase $listCompanies,
        private readonly CreateCompanyUseCase $createCompany,
        private readonly UpdateCompanyUseCase $updateCompany,
        private readonly DeleteCompanyUseCase $deleteCompany,
        private readonly AddCompanyContactUseCase $addContact,
        private readonly DeleteCompanyContactUseCase $deleteContact,
        private readonly UpdateCompanySettingsUseCase $updateSettings,
        private readonly RequestValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function index(array $request): string
    {
        $page = (int) ($request['page'] ?? 1);
        $pageSize = (int) ($request['page_size'] ?? 15);
        $result = ($this->listCompanies)(['page' => $page, 'page_size' => $pageSize]);
        $payload = $result->toArray();

        return $this->ok($payload['data'], $payload['meta']);
    }

    /**
     * @param array<string, mixed> $request
     */
    public function store(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'legal_name' => static fn ($value): bool => is_string($value) && $value !== '',
                'country_id' => static fn ($value): bool => is_numeric($value),
                'default_currency_id' => static fn ($value): bool => is_numeric($value),
                'timezone' => static fn ($value): bool => is_string($value) && $value !== '',
                'trade_name' => static fn ($value): bool => $value === null || is_string($value),
                'contacts' => static fn ($value): bool => $value === null || is_array($value),
                'settings' => static fn ($value): bool => $value === null || is_array($value),
            ]);

            $result = ($this->createCompany)($payload);

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
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->updateCompany)(array_merge($request, $payload));

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function destroy(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->deleteCompany)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function addContact(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'name' => static fn ($value): bool => is_string($value) && $value !== '',
                'email' => static fn ($value): bool => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                'phone' => static fn ($value): bool => $value === null || is_string($value),
                'type' => static fn ($value): bool => $value === null || is_string($value),
                'is_primary' => static fn ($value): bool => $value === null || is_bool($value),
            ]);

            $contact = ($this->addContact)($payload);

            return $this->created(['contact' => $contact]);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function removeContact(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'contact_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->deleteContact)($payload);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function updateSettings(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
                'settings' => static fn ($value): bool => is_array($value),
            ]);

            $settings = ($this->updateSettings)($payload);

            return $this->ok(['settings' => $settings]);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }
}
