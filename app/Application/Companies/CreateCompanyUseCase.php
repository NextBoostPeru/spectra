<?php

declare(strict_types=1);

namespace App\Application\Companies;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyRepositoryInterface;
use App\Domain\Repositories\CompanySettingsRepositoryInterface;
use App\Domain\Repositories\CompanyContactRepositoryInterface;

class CreateCompanyUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly CompanySettingsRepositoryInterface $settings,
        private readonly CompanyContactRepositoryInterface $contacts,
    ) {
    }

    /**
     * @param array{legal_name:string,trade_name?:string|null,country_id:int,default_currency_id:int,timezone:string,settings?:array<string,mixed>,contacts?:list<array<string,mixed>>} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new ApplicationException('Datos inválidos para crear compañía.');
        }

        foreach (['legal_name', 'country_id', 'default_currency_id', 'timezone'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new ApplicationException(sprintf('Falta %s', $required));
            }
        }

        $company = $this->companies->create([
            'legal_name' => (string) $input['legal_name'],
            'trade_name' => $input['trade_name'] ?? null,
            'country_id' => (int) $input['country_id'],
            'default_currency_id' => (int) $input['default_currency_id'],
            'timezone' => (string) $input['timezone'],
            'status' => 'active',
        ]);

        $settings = $this->settings->upsert($company->id(), (array) ($input['settings'] ?? []));

        $contacts = [];

        foreach ($input['contacts'] ?? [] as $contact) {
            if (! is_array($contact) || empty($contact['name']) || empty($contact['email'])) {
                continue;
            }

            $contacts[] = $this->contacts->create($company->id(), $contact)->toArray();
        }

        return [
            'company' => [
                'id' => $company->id(),
                'legal_name' => $company->legalName(),
                'trade_name' => $company->tradeName(),
                'country_id' => $company->countryId(),
                'default_currency_id' => $company->defaultCurrencyId(),
                'timezone' => $company->timezone(),
            ],
            'settings' => $settings->toArray(),
            'contacts' => $contacts,
        ];
    }
}
