<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests;

use InvalidArgumentException;

/**
 * Validador simple para peticiones HTTP sin framework.
 */
class RequestValidator
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, callable(mixed): bool> $rules
     * @return array<string, mixed>
     */
    public function validate(array $input, array $rules): array
    {
        $validated = [];

        foreach ($rules as $field => $rule) {
            $value = $input[$field] ?? null;

            if (! $rule($value)) {
                throw new InvalidArgumentException("Campo inválido: {$field}");
            }

            $validated[$field] = $value;
        }

        return $validated;
    }
}
