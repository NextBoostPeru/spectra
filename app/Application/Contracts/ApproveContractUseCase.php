<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use DateTimeImmutable;
use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\ContractRepositoryInterface;
use App\Domain\Repositories\LegalApprovalRepositoryInterface;

class ApproveContractUseCase implements UseCase
{
    public function __construct(
        private readonly ContractRepositoryInterface $contracts,
        private readonly LegalApprovalRepositoryInterface $approvals,
    ) {
    }

    /**
     * @param array{company_id:string,contract_id:string,contract_version_id?:string|null,status:string,reviewed_by_company_user_id?:string|null,comment?:string|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Datos inválidos para aprobación.');
        }

        foreach (['company_id', 'contract_id', 'status'] as $required) {
            if (empty($input[$required])) {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $contract = $this->contracts->findById((string) $input['contract_id'], (string) $input['company_id']);
        if ($contract === null) {
            throw new InvalidArgumentException('Contrato no encontrado para aprobación legal.');
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->approvals->create([
            'company_id' => $contract->companyId(),
            'contract_id' => $contract->id(),
            'contract_version_id' => $input['contract_version_id'] ?? $contract->toArray()['current_version_id'],
            'status' => (string) $input['status'],
            'reviewed_by_company_user_id' => $input['reviewed_by_company_user_id'] ?? null,
            'reviewed_at' => $now,
            'comment' => $input['comment'] ?? null,
        ]);

        $contract = $this->contracts->update($contract->id(), $contract->companyId(), [
            'legal_approved_by_company_user_id' => $input['reviewed_by_company_user_id'] ?? null,
            'legal_approved_at' => $now,
        ]);

        return $contract->toArray();
    }
}
