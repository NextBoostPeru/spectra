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

    /**
     * @param array<string, scalar|null> $filters
     * @param list<string> $allowed
     * @return array<string, scalar|null>
     */
    public function whitelistFilters(array $filters, array $allowed): array
    {
        $sanitized = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $filters)) {
                $sanitized[$field] = $filters[$field];
            }
        }

        return $sanitized;
    }

    /**
     * @param list<string> $allowed
     * @return array{sort_by:string|null,direction:string}
     */
    public function whitelistSort(?string $sortBy, ?string $direction, array $allowed): array
    {
        $field = $sortBy !== null && in_array($sortBy, $allowed, true) ? $sortBy : ($allowed[0] ?? null);
        $dir = strtolower($direction ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return [
            'sort_by' => $field,
            'direction' => $dir,
        ];
    }
}
