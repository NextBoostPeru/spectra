<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\ContractRepositoryInterface;
use App\Domain\Repositories\ContractTemplateRepositoryInterface;
use App\Domain\Repositories\ContractVersionRepositoryInterface;

class AddContractVersionUseCase implements UseCase
{
    public function __construct(
        private readonly ContractRepositoryInterface $contracts,
        private readonly ContractTemplateRepositoryInterface $templates,
        private readonly ContractVersionRepositoryInterface $versions,
    ) {
    }

    /**
     * @param array{company_id:string,contract_id:string,body:string,template_id?:string|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Payload inválido.');
        }

        foreach (['company_id', 'contract_id', 'body'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $contract = $this->contracts->findById((string) $input['contract_id'], (string) $input['company_id']);
        if ($contract === null) {
            throw new InvalidArgumentException('Contrato no encontrado.');
        }

        $templateId = $input['template_id'] ?? $contract->toArray()['template_id'];
        $template = $this->templates->findById($templateId, $contract->companyId());
        $templateVersion = $template?->version();

        $versionNumber = $this->versions->latestVersionNumber($contract->id()) + 1;

        $version = $this->versions->create([
            'contract_id' => $contract->id(),
            'template_id' => $template?->id(),
            'template_version' => $templateVersion,
            'version_number' => $versionNumber,
            'body_snapshot' => (string) $input['body'],
            'status' => 'draft',
        ]);

        $contract = $this->contracts->update($contract->id(), $contract->companyId(), [
            'current_version_id' => $version->id(),
            'status' => 'draft',
        ]);

        return [
            'contract' => $contract->toArray(),
            'version' => $version->toArray(),
        ];
    }
}
