<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

use App\Application\Authorization\AuthorizationService;
use App\Application\Exceptions\ApplicationException;
use App\Interface\Http\Responses\ApiResponse;

class AuthorizationMiddleware
{
    public function __construct(
        private readonly ActiveCompanyResolver $companyResolver,
        private readonly AuthorizationService $authorization,
    ) {
    }

    public function can(string $permission): Middleware
    {
        return new class($this->companyResolver, $this->authorization, $permission) implements Middleware {
            public function __construct(
                private readonly ActiveCompanyResolver $companyResolver,
                private readonly AuthorizationService $authorization,
                private readonly string $permission,
            ) {
            }

            /**
             * @param array<string, mixed> $request
             */
            public function __invoke(array $request, callable $next): mixed
            {
                try {
                    $context = $this->companyResolver->resolve($request);
                } catch (ApplicationException $exception) {
                    return ApiResponse::error($exception->getMessage(), 401);
                }

                $platformRole = (string) ($context['claims']['platform_role'] ?? 'none');

                if (! $this->authorization->hasCompanyPermission($context['company_user_id'], $this->permission, $platformRole)) {
                    return ApiResponse::error('No tienes permisos para realizar esta acción.', 403);
                }

                $request['auth_context'] = $context;

                return $next($request);
            }
        };
    }
}
