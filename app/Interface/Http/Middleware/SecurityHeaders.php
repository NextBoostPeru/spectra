<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

class SecurityHeaders
{
    /**
     * @param array{force_https:bool,csp:string,referrer_policy:string,frame_options:string,cors:array<string,mixed>,rate_limit:array<string,int>} $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function enforceHttps(): void
    {
        if (! $this->config['force_https']) {
            return;
        }

        if ($this->isHttps()) {
            return;
        }

        if (! isset($_SERVER['HTTP_HOST'])) {
            return;
        }

        $target = 'https://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $target, true, 301);
        exit;
    }

    public function applySecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: ' . $this->config['frame_options']);
        header('Referrer-Policy: ' . $this->config['referrer_policy']);
        header('Content-Security-Policy: ' . $this->config['csp']);

        if ($this->isHttps()) {
            header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
        }
    }

    public function applyCors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $allowedOrigins = $this->config['cors']['allowed_origins'] ?? [];

        if ($origin !== null && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: ' . ($this->config['cors']['allowed_methods'] ?? 'GET,POST'));
        header('Access-Control-Allow-Headers: ' . ($this->config['cors']['allowed_headers'] ?? 'Authorization,Content-Type'));
        header('Access-Control-Max-Age: ' . ($this->config['cors']['max_age'] ?? 600));
    }

    private function isHttps(): bool
    {
        $forwardedProto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
        $https = $_SERVER['HTTPS'] ?? null;

        return ($https !== null && $https !== 'off') || $forwardedProto === 'https';
    }
}
