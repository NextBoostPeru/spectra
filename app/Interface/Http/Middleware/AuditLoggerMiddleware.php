<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

use App\Domain\Repositories\AuditLogRepositoryInterface;

class AuditLoggerMiddleware implements Middleware
{
    public function __construct(private readonly AuditLogRepositoryInterface $auditLogs)
    {
    }

    public function __invoke(array $request, callable $next): mixed
    {
        $response = $next($request);

        $this->auditLogs->record([
            'company_id' => $request['company_id'] ?? null,
            'actor_user_id' => (string) ($request['user_id'] ?? 'anonymous'),
            'actor_company_user_id' => $request['company_user_id'] ?? null,
            'action' => $request['action'] ?? ($request['path'] ?? 'unknown'),
            'object_type' => $request['object_type'] ?? null,
            'object_id' => $request['object_id'] ?? null,
            'ip' => $request['ip'] ?? null,
            'user_agent' => $request['user_agent'] ?? null,
            'metadata' => $this->sanitizeMetadata($request['meta'] ?? []),
        ]);

        return $response;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $sensitiveKeys = ['password', 'token', 'authorization', 'refresh_token', 'document', 'secret'];
        $clean = [];

        foreach ($metadata as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $clean[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitizeMetadata($value);
                continue;
            }

            if (is_string($value) && strlen($value) > 256) {
                $clean[$key] = substr($value, 0, 256) . '...';
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
