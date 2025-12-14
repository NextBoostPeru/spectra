<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\ContractTemplateRepositoryInterface;

class CreateContractTemplateUseCase implements UseCase
{
    public function __construct(private readonly ContractTemplateRepositoryInterface $templates)
    {
    }

    /**
     * @param array{company_id:string,type:string,country_id:int,language_code?:string,title:string,body:string,variables_schema?:array|null} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Datos inválidos para template.');
        }

        foreach (['company_id', 'type', 'country_id', 'title', 'body'] as $required) {
            if (! isset($input[$required]) || $input[$required] === '') {
                throw new InvalidArgumentException(sprintf('Falta %s', $required));
            }
        }

        $template = $this->templates->create([
            'company_id' => (string) $input['company_id'],
            'type' => (string) $input['type'],
            'country_id' => (int) $input['country_id'],
            'language_code' => $input['language_code'] ?? 'es',
            'title' => (string) $input['title'],
            'body' => (string) $input['body'],
            'variables_schema' => $input['variables_schema'] ?? null,
        ]);

        return $template->toArray();
    }
}
