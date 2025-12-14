<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Contracts\UseCase;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\UserIdentityRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\UserSessionRepositoryInterface;

class OidcLoginUseCase implements UseCase
{
    public function __construct(
        private readonly UserIdentityRepositoryInterface $identities,
        private readonly UserRepositoryInterface $users,
        private readonly UserSessionRepositoryInterface $sessions,
        private readonly OidcValidator $validator,
        private readonly JwtTokenManager $jwt,
        private readonly int $refreshTtlDays,
    ) {
    }

    /**
     * @param array{provider:string,state:string,expected_state:string,nonce:string,expected_nonce:string,id_token_claims:array<string,mixed>,ip?:string|null,user_agent?:string|null} $input
     * @return array{access_token:string,refresh_token:string,expires_in:int,refresh_expires_in:int}
     */
    public function __invoke(mixed $input): array
    {
        $provider = (string) ($input['provider'] ?? '');
        $state = (string) ($input['state'] ?? '');
        $expectedState = (string) ($input['expected_state'] ?? '');
        $nonce = (string) ($input['nonce'] ?? '');
        $expectedNonce = (string) ($input['expected_nonce'] ?? '');
        $claims = $input['id_token_claims'] ?? [];
        $ip = $input['ip'] ?? null;
        $userAgent = $input['user_agent'] ?? null;

        if ($provider === '' || $state === '' || $expectedState === '' || $nonce === '' || $expectedNonce === '') {
            throw new ApplicationException('Faltan datos de validación SSO.');
        }

        if (! is_array($claims)) {
            throw new ApplicationException('Claims del ID token inválidos.');
        }

        $this->validator->validate($provider, $state, $expectedState, $nonce, $expectedNonce, $claims);

        $subject = (string) ($claims['sub'] ?? '');

        if ($subject === '') {
            throw new ApplicationException('El token no incluye subject.');
        }

        $identity = $this->identities->findByProviderSubject($provider, $subject);

        if ($identity === null) {
            throw new ApplicationException('Identidad no vinculada. Solicita enrolamiento.');
        }

        $user = $this->users->findById($identity->userId());

        if ($user === null) {
            throw new ApplicationException('El usuario vinculado ya no existe.');
        }

        $user->assertCanLogin();

        $refreshToken = bin2hex(random_bytes(64));
        $refreshHash = hash('sha256', $refreshToken);

        $session = $this->sessions->create($user->id(), $refreshHash, $ip, $userAgent);
        $accessToken = $this->jwt->createAccessToken($user->id(), $session->id(), ['idp' => $provider]);

        $this->identities->linkIdentity($user->id(), $provider, $subject, (bool) ($claims['email_verified'] ?? false));

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->jwt->accessTtlSeconds(),
            'refresh_expires_in' => $this->refreshTtlDays * 86400,
        ];
    }
}
