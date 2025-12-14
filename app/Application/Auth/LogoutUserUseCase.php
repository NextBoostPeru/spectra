<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\UserSessionRepositoryInterface;

class LogoutUserUseCase implements UseCase
{
    public function __construct(private readonly UserSessionRepositoryInterface $sessions)
    {
    }

    /**
     * @param array{refresh_token:string} $input
     */
    public function __invoke(mixed $input): bool
    {
        $refreshToken = (string) ($input['refresh_token'] ?? '');

        if ($refreshToken === '') {
            throw new ApplicationException('Refresh token requerido.');
        }

        $hash = hash('sha256', $refreshToken);
        $this->sessions->revokeByRefreshHash($hash);

        return true;
    }
}
