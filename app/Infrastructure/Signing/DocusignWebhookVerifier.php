<?php

declare(strict_types=1);

namespace App\Infrastructure\Signing;

class DocusignWebhookVerifier
{
    public function __construct(private readonly string $secret)
    {
    }

    public function verify(string $payload, string $signatureHeader): bool
    {
        if ($this->secret === '') {
            return false;
        }

        $computed = base64_encode(hash_hmac('sha256', $payload, $this->secret, true));

        return hash_equals($computed, $signatureHeader);
    }
}
