<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Exceptions\ApplicationException;

class OidcValidator
{
    public function __construct(private readonly array $providers)
    {
    }

    /**
     * @param array<string, mixed> $idTokenClaims
     */
    public function validate(string $provider, string $state, string $expectedState, string $nonce, string $expectedNonce, array $idTokenClaims): void
    {
        if (! isset($this->providers[$provider])) {
            throw new ApplicationException('Proveedor SSO no soportado.');
        }

        if (! hash_equals($expectedState, $state)) {
            throw new ApplicationException('State inválido, posible ataque CSRF.');
        }

        if (! hash_equals($expectedNonce, $nonce)) {
            throw new ApplicationException('Nonce inválido.');
        }

        $providerConfig = $this->providers[$provider];

        if (($idTokenClaims['iss'] ?? '') !== $providerConfig['issuer']) {
            throw new ApplicationException('Issuer SSO inválido.');
        }

        if (($idTokenClaims['aud'] ?? '') !== $providerConfig['audience']) {
            throw new ApplicationException('Audiencia SSO inválida.');
        }

        $now = time();
        if (($idTokenClaims['exp'] ?? 0) < $now) {
            throw new ApplicationException('Token SSO expirado.');
        }
    }
}
