<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\UserSessionRepositoryInterface;

class SwitchActiveCompanyUseCase implements UseCase
{
    public function __construct(
        private readonly CompanyUserRepositoryInterface $companyUsers,
        private readonly UserRepositoryInterface $users,
        private readonly UserSessionRepositoryInterface $sessions,
        private readonly JwtTokenManager $jwt,
        private readonly int $refreshTtlDays,
    ) {
    }

    /**
     * @param array{user_id:string,session_id:string,company_id:string,ip?:string|null,user_agent?:string|null} $input
     * @return array{access_token:string,refresh_token:string,expires_in:int,refresh_expires_in:int}
     */
    public function __invoke(mixed $input): array
    {
        $userId = (string) ($input['user_id'] ?? '');
        $sessionId = (string) ($input['session_id'] ?? '');
        $companyId = (string) ($input['company_id'] ?? '');
        $ip = $input['ip'] ?? null;
        $userAgent = $input['user_agent'] ?? null;

        if ($userId === '' || $sessionId === '' || $companyId === '') {
            throw new ApplicationException('Faltan datos para cambiar de empresa.');
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new ApplicationException('Usuario no encontrado.');
        }

        try {
            $membership = $this->companyUsers->setActiveCompany($userId, $companyId);
            $user->assertCanLogin();
            $membership->assertUsable();
        } catch (\Throwable $exception) {
            throw new ApplicationException($exception->getMessage(), previous: $exception);
        }

        $refreshToken = bin2hex(random_bytes(64));
        $refreshHash = hash('sha256', $refreshToken);

        try {
            $session = $this->sessions->rotateToken($sessionId, $refreshHash, $ip, $userAgent);
        } catch (\Throwable $exception) {
            throw new ApplicationException('No se pudo actualizar la sesión para la empresa seleccionada.', previous: $exception);
        }
        $accessToken = $this->jwt->createAccessToken($userId, $session->id(), [
            'company_id' => $membership->companyId(),
            'platform_role' => $user->platformRole(),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->jwt->accessTtlSeconds(),
            'refresh_expires_in' => $this->refreshTtlDays * 86400,
        ];
    }
}
