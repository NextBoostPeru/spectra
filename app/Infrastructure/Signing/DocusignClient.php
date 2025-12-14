<?php

declare(strict_types=1);

namespace App\Infrastructure\Signing;

use App\Infrastructure\Logging\Logger;

class DocusignClient
{
    public function __construct(private readonly array $config, private readonly Logger $logger)
    {
    }

    /**
     * @param array<int, array{name:string,email:string,role?:string|null}> $signers
     *
     * @return array{envelope_id:string,status:string,webhook_key:string}
     */
    public function createEnvelope(string $subject, string $documentContent, array $signers, string $webhookKey): array
    {
        $envelopeId = 'DS-' . $this->uuid();

        $this->logger->info('Envelope generado', [
            'envelope_id' => $envelopeId,
            'subject' => $subject,
            'signers' => array_map(static fn ($signer): array => [
                'name' => $signer['name'],
                'email' => $signer['email'],
                'role' => $signer['role'] ?? 'counterparty',
            ], $signers),
            'webhook_key' => $webhookKey,
            'base_uri' => $this->config['base_uri'] ?? null,
        ]);

        return [
            'envelope_id' => $envelopeId,
            'status' => 'sent',
            'webhook_key' => $webhookKey,
        ];
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }
}
