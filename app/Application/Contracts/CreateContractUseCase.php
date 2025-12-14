<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\ContractRepositoryInterface;
use App\Domain\Repositories\ContractTemplateRepositoryInterface;
use App\Domain\Repositories\ContractVersionRepositoryInterface;

class CreateContractUseCase implements UseCase
{
    public function __construct(
        private readonly ContractRepositoryInterface $contracts,
        private readonly ContractTemplateRepositoryInterface $templates,
        private readonly ContractVersionRepositoryInterface $versions,
    ) {
    }

    /**
     * @param array{company_id:string,freelancer_id:string,template_id:string,jurisdiction_country_id:int,payment_type:string,rate_currency_id:int,project_id?:string|null,title?:string|null,counterparty_name?:string|null,counterparty_email?:string|null,start_date?:string|null,end_date?:string|null,notice_days?:int|null,rate_amount?:float|null,retainer_amount?:float|null,body?:string|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Payload inválido para contrato.');
        }

        foreach (['company_id', 'freelancer_id', 'template_id', 'jurisdiction_country_id', 'payment_type', 'rate_currency_id'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $template = $this->templates->findById((string) $input['template_id'], (string) $input['company_id']);
        if ($template === null) {
            throw new InvalidArgumentException('Template no encontrado para la empresa.');
        }

        $contract = $this->contracts->create([
            'company_id' => (string) $input['company_id'],
            'freelancer_id' => (string) $input['freelancer_id'],
            'template_id' => $template->id(),
            'jurisdiction_country_id' => (int) $input['jurisdiction_country_id'],
            'payment_type' => (string) $input['payment_type'],
            'rate_currency_id' => (int) $input['rate_currency_id'],
            'project_id' => $input['project_id'] ?? null,
            'title' => $input['title'] ?? $template->toArray()['title'],
            'counterparty_name' => $input['counterparty_name'] ?? null,
            'counterparty_email' => $input['counterparty_email'] ?? null,
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
            'notice_days' => $input['notice_days'] ?? 0,
            'rate_amount' => $input['rate_amount'] ?? null,
            'retainer_amount' => $input['retainer_amount'] ?? null,
        ]);

        $versionNumber = $this->versions->latestVersionNumber($contract->id()) + 1;
        $version = $this->versions->create([
            'contract_id' => $contract->id(),
            'template_id' => $template->id(),
            'template_version' => $template->version(),
            'version_number' => $versionNumber,
            'body_snapshot' => $input['body'] ?? $template->toArray()['body'],
            'status' => 'draft',
        ]);

        $contract = $this->contracts->update($contract->id(), $contract->companyId(), [
            'current_version_id' => $version->id(),
        ]);

        return [
            'contract' => $contract->toArray(),
            'version' => $version->toArray(),
        ];
    }
}
