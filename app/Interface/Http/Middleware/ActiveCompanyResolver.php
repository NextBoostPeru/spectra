<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

use App\Application\Auth\JwtTokenManager;
use App\Application\Exceptions\ApplicationException;
use App\Domain\Repositories\CompanyUserRepositoryInterface;

class ActiveCompanyResolver
{
    public function __construct(
        private readonly JwtTokenManager $jwt,
        private readonly CompanyUserRepositoryInterface $companyUsers,
        private readonly array $tenantConfig,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     * @return array{user_id:string,company_user_id:string,session_id:string,company_id:string,claims:array<string,mixed>}
     */
    public function resolve(array $request): array
    {
        $token = $this->extractBearerToken($request);
        $claims = $this->jwt->decode($token);

        $userId = (string) ($claims['sub'] ?? '');
        $sessionId = (string) ($claims['sid'] ?? '');

        if ($userId === '' || $sessionId === '') {
            throw new ApplicationException('Token inválido o incompleto.');
        }

        $headerName = strtolower((string) ($this->tenantConfig['company_header'] ?? 'x-company-id'));
        $claimsKey = (string) ($this->tenantConfig['company_claim'] ?? 'company_id');
        $headers = array_change_key_case((array) ($request['headers'] ?? []), CASE_LOWER);

        $companyFromHeader = $headers[$headerName] ?? null;
        $companyFromToken = $claims[$claimsKey] ?? null;
        $companyId = $companyFromHeader !== null ? (string) $companyFromHeader : (string) $companyFromToken;

        if ($companyId === '') {
            throw new ApplicationException('No se pudo resolver la empresa activa.');
        }

        try {
            $membership = $this->companyUsers->assertMembership($userId, $companyId);
        } catch (\Throwable $exception) {
            throw new ApplicationException('El usuario no tiene acceso a la empresa solicitada.', previous: $exception);
        }

        return [
            'user_id' => $userId,
            'company_user_id' => $membership->id(),
            'session_id' => $sessionId,
            'company_id' => $membership->companyId(),
            'claims' => $claims,
        ];
    }

    /**
     * @param array<string, mixed> $request
     */
    private function extractBearerToken(array $request): string
    {
        $headers = array_change_key_case((array) ($request['headers'] ?? []), CASE_LOWER);
        $authorization = (string) ($headers['authorization'] ?? $request['authorization'] ?? '');
        $token = $request['access_token'] ?? '';

        if ($token === '' && is_string($authorization) && str_starts_with(trim($authorization), 'Bearer ')) {
            $token = trim(substr($authorization, 7));
        }

        if (! is_string($token) || $token === '') {
            throw new ApplicationException('Falta el access token.');
        }

        return $token;
    }
}
