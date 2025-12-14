<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use DateTimeImmutable;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\ContractRepositoryInterface;
use App\Infrastructure\Logging\Logger;

class NotifyExpiringContractsJob implements UseCase
{
    public function __construct(private readonly ContractRepositoryInterface $contracts, private readonly Logger $logger)
    {
    }

    /**
     * @param array{company_id:string,days?:int|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || empty($input['company_id'])) {
            throw new \InvalidArgumentException('company_id requerido para job de expiración.');
        }

        $days = isset($input['days']) ? (int) $input['days'] : (int) (env('CONTRACT_EXPIRATION_WARNING_DAYS', 15));
        $expiring = $this->contracts->findExpiringWithin((string) $input['company_id'], $days);

        $notified = [];
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($expiring as $contract) {
            $this->logger->info('Contrato próximo a vencer', [
                'contract_id' => $contract->id(),
                'company_id' => $contract->companyId(),
                'end_date' => $contract->endDate(),
            ]);

            $this->contracts->update($contract->id(), $contract->companyId(), [
                'last_expiration_notified_at' => $now,
            ]);

            $notified[] = $contract->toArray();
        }

        return ['notified' => $notified];
    }
}
