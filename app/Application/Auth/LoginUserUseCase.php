<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Exceptions\DomainException;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\UserSessionRepositoryInterface;

class LoginUserUseCase implements UseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserSessionRepositoryInterface $sessions,
        private readonly PasswordHasher $hasher,
        private readonly JwtTokenManager $jwt,
        private readonly LoginAttemptLimiter $limiter,
        private readonly int $refreshTtlDays,
    ) {
    }

    /**
     * @param array{email:string,password:string,ip?:string|null,user_agent?:string|null} $input
     * @return array{access_token:string,refresh_token:string,expires_in:int,refresh_expires_in:int}
     */
    public function __invoke(mixed $input): array
    {
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $ip = $input['ip'] ?? null;
        $userAgent = $input['user_agent'] ?? null;

        if ($email === '' || $password === '') {
            throw new ApplicationException('Credenciales incompletas.');
        }

        $this->limiter->ensureNotLocked($email);

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            $this->limiter->registerFailure($email);
            throw new ApplicationException('Credenciales inválidas.');
        }

        try {
            $user->assertCanLogin();
        } catch (DomainException $exception) {
            $this->limiter->registerFailure($email);
            throw new ApplicationException($exception->getMessage(), previous: $exception);
        }

        if (! $this->hasher->verify($password, $user->passwordHash())) {
            $this->limiter->registerFailure($email);
            throw new ApplicationException('Credenciales inválidas.');
        }

        $this->limiter->registerSuccess($email);

        $refreshToken = bin2hex(random_bytes(64));
        $refreshHash = hash('sha256', $refreshToken);

        $session = $this->sessions->create($user->id(), $refreshHash, $ip, $userAgent);
        $accessToken = $this->jwt->createAccessToken($user->id(), $session->id());

        $this->users->recordLogin($user->id(), $ip, $userAgent);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->jwt->accessTtlSeconds(),
            'refresh_expires_in' => $this->refreshTtlDays * 86400,
        ];
    }
}
