<?php

declare(strict_types=1);

namespace App\Application\Contracts;

/**
 * Cada caso de uso encapsula una unidad de aplicación.
 *
 * @template TInput
 * @template TOutput
 */
interface UseCase
{
    /**
     * @param TInput $input
     * @return TOutput
     */
    public function __invoke(mixed $input): mixed;
}
