<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\UserSessionRepositoryInterface;

class RefreshTokenUseCase implements UseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CompanyUserRepositoryInterface $companyUsers,
        private readonly UserSessionRepositoryInterface $sessions,
        private readonly JwtTokenManager $jwt,
        private readonly int $refreshTtlDays,
    ) {
    }

    /**
     * @param array{refresh_token:string,ip?:string|null,user_agent?:string|null} $input
     * @return array{access_token:string,refresh_token:string,expires_in:int,refresh_expires_in:int}
     */
    public function __invoke(mixed $input): array
    {
        $refreshToken = (string) ($input['refresh_token'] ?? '');
        $ip = $input['ip'] ?? null;
        $userAgent = $input['user_agent'] ?? null;

        if ($refreshToken === '') {
            throw new ApplicationException('Refresh token requerido.');
        }

        $hash = hash('sha256', $refreshToken);
        $session = $this->sessions->findActiveByRefreshHash($hash);

        if ($session === null || ! $session->isActive()) {
            throw new ApplicationException('Sesión inválida o revocada.');
        }

        if ($session->isExpired($this->refreshTtlDays)) {
            $this->sessions->revokeByRefreshHash($hash);
            throw new ApplicationException('El refresh token expiró.');
        }

        $user = $this->users->findById($session->userId());

        if ($user === null) {
            $this->sessions->revokeByRefreshHash($hash);
            throw new ApplicationException('La cuenta ya no está disponible.');
        }

        try {
            $user->assertCanLogin();
        } catch (\Throwable $exception) {
            $this->sessions->revokeByRefreshHash($hash);
            throw new ApplicationException($exception->getMessage(), previous: $exception);
        }

        $activeCompany = $this->companyUsers->findActiveForUser($user->id());

        if ($activeCompany === null) {
            throw new ApplicationException('El usuario no tiene compañías asignadas.');
        }

        if (! $activeCompany->isActive()) {
            $activeCompany = $this->companyUsers->setActiveCompany($user->id(), $activeCompany->companyId());
        }

        $newRefreshToken = bin2hex(random_bytes(64));
        $newRefreshHash = hash('sha256', $newRefreshToken);

        $updatedSession = $this->sessions->rotateToken($session->id(), $newRefreshHash, $ip, $userAgent);
        $accessToken = $this->jwt->createAccessToken($updatedSession->userId(), $updatedSession->id(), [
            'company_id' => $activeCompany->companyId(),
            'platform_role' => $user->platformRole(),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => $this->jwt->accessTtlSeconds(),
            'refresh_expires_in' => $this->refreshTtlDays * 86400,
        ];
    }
}
