<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Application\Auth\LoginUserUseCase;
use App\Application\Auth\OidcLoginUseCase;
use App\Application\Auth\LogoutUserUseCase;
use App\Application\Auth\RefreshTokenUseCase;
use App\Application\Auth\SwitchActiveCompanyUseCase;
use App\Application\Exceptions\ApplicationException;
use App\Interface\Http\Middleware\ActiveCompanyResolver;
use App\Interface\Http\Middleware\RateLimiter;
use App\Interface\Http\Requests\RequestValidator;
use InvalidArgumentException;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginUserUseCase $login,
        private readonly LogoutUserUseCase $logout,
        private readonly RefreshTokenUseCase $refresh,
        private readonly SwitchActiveCompanyUseCase $switchCompany,
        private readonly ?OidcLoginUseCase $oidcLogin,
        private readonly RequestValidator $validator,
        private readonly RateLimiter $rateLimiter,
        private readonly ActiveCompanyResolver $companyResolver,
        private readonly array $securityConfig,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function login(array $request): string
    {
        $clientKey = ($request['ip'] ?? 'unknown') . ':login';
        $limit = $this->securityConfig['rate_limit'];

        if (! $this->rateLimiter->allow($clientKey, $limit['max_attempts'], $limit['window_seconds'])) {
            return $this->error('Too many login attempts', 429);
        }

        try {
            $payload = $this->validator->validate($request, [
                'email' => static fn ($value): bool => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
                'password' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->login)([
                'email' => $payload['email'],
                'password' => $payload['password'],
                'ip' => $request['ip'] ?? null,
                'user_agent' => $request['user_agent'] ?? null,
            ]);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function refresh(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'refresh_token' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $result = ($this->refresh)([
                'refresh_token' => $payload['refresh_token'],
                'ip' => $request['ip'] ?? null,
                'user_agent' => $request['user_agent'] ?? null,
            ]);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function logout(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'refresh_token' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            ($this->logout)(['refresh_token' => $payload['refresh_token']]);

            return $this->ok(['message' => 'Sesión cerrada']);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function ssoCallback(array $request): string
    {
        if ($this->oidcLogin === null) {
            return $this->error('SSO no configurado.', 501);
        }

        try {
            $payload = $this->validator->validate($request, [
                'provider' => static fn ($value): bool => is_string($value) && in_array($value, ['google', 'microsoft'], true),
                'state' => static fn ($value): bool => is_string($value) && $value !== '',
                'expected_state' => static fn ($value): bool => is_string($value) && $value !== '',
                'nonce' => static fn ($value): bool => is_string($value) && $value !== '',
                'expected_nonce' => static fn ($value): bool => is_string($value) && $value !== '',
                'id_token_claims' => static fn ($value): bool => is_array($value),
            ]);

            $result = ($this->oidcLogin)([
                'provider' => $payload['provider'],
                'state' => $payload['state'],
                'expected_state' => $payload['expected_state'],
                'nonce' => $payload['nonce'],
                'expected_nonce' => $payload['expected_nonce'],
                'id_token_claims' => $payload['id_token_claims'],
                'ip' => $request['ip'] ?? null,
                'user_agent' => $request['user_agent'] ?? null,
            ]);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    public function switchCompany(array $request): string
    {
        try {
            $payload = $this->validator->validate($request, [
                'company_id' => static fn ($value): bool => is_string($value) && $value !== '',
            ]);

            $context = $this->companyResolver->resolve($request);

            $result = ($this->switchCompany)([
                'user_id' => $context['user_id'],
                'session_id' => $context['session_id'],
                'company_id' => $payload['company_id'],
                'ip' => $request['ip'] ?? null,
                'user_agent' => $request['user_agent'] ?? null,
            ]);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }
}
