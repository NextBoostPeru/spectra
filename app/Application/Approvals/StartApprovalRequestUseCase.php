<?php

declare(strict_types=1);

namespace App\Application\Approvals;

use InvalidArgumentException;
use App\Application\Contracts\UseCase;

class StartApprovalRequestUseCase implements UseCase
{
    public function __construct(private readonly ApprovalEngine $engine)
    {
    }

    /**
     * @param array{company_id:string,object_type:string,object_id:string,created_by_company_user_id?:string|null,amount?:float|null,currency_id?:int|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Datos inválidos para solicitud de aprobación.');
        }

        foreach (['company_id', 'object_type', 'object_id'] as $required) {
            if (empty($input[$required])) {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $result = $this->engine->start(
            (string) $input['company_id'],
            (string) $input['object_type'],
            (string) $input['object_id'],
            $input['created_by_company_user_id'] ?? null,
            isset($input['amount']) ? (float) $input['amount'] : null,
            isset($input['currency_id']) ? (int) $input['currency_id'] : null,
        );

        return [
            'request' => $result['request']->toArray(),
            'steps' => $result['steps'],
        ];
    }
}
