<?php

declare(strict_types=1);

namespace App\Application\Approvals;

use InvalidArgumentException;
use App\Application\Contracts\UseCase;

class RequestPayrollRunApprovalUseCase implements UseCase
{
    public function __construct(private readonly StartApprovalRequestUseCase $starter)
    {
    }

    /**
     * @param array{company_id:string,payroll_run_id:string,created_by_company_user_id?:string|null,amount?:float|null,currency_id?:int|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Payload inválido para aprobación de nómina.');
        }

        foreach (['company_id', 'payroll_run_id'] as $required) {
            if (empty($input[$required])) {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        return ($this->starter)([
            'company_id' => (string) $input['company_id'],
            'object_type' => 'payroll_run',
            'object_id' => (string) $input['payroll_run_id'],
            'created_by_company_user_id' => $input['created_by_company_user_id'] ?? null,
            'amount' => $input['amount'] ?? null,
            'currency_id' => $input['currency_id'] ?? null,
        ]);
    }
}
