<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use DateTimeImmutable;
use InvalidArgumentException;
use App\Application\Contracts\UseCase;
use App\Domain\Repositories\ContractRepositoryInterface;
use App\Domain\Repositories\ContractSignerRepositoryInterface;
use App\Domain\Repositories\ContractVersionRepositoryInterface;
use App\Domain\Repositories\DocusignEnvelopeRepositoryInterface;
use App\Domain\Repositories\DocusignWebhookEventRepositoryInterface;
use App\Infrastructure\Signing\DocusignWebhookVerifier;

class HandleDocusignWebhookUseCase implements UseCase
{
    public function __construct(
        private readonly ContractRepositoryInterface $contracts,
        private readonly ContractVersionRepositoryInterface $versions,
        private readonly ContractSignerRepositoryInterface $signers,
        private readonly DocusignEnvelopeRepositoryInterface $envelopes,
        private readonly DocusignWebhookEventRepositoryInterface $events,
        private readonly DocusignWebhookVerifier $verifier,
    ) {
    }

    /**
     * @param array{payload:string,signature:string} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input) || ! isset($input['payload'], $input['signature'])) {
            throw new InvalidArgumentException('Webhook inválido.');
        }

        $payload = (string) $input['payload'];
        $signature = (string) $input['signature'];
        $payloadData = json_decode($payload, true);

        if (! is_array($payloadData) || ! isset($payloadData['envelopeId'])) {
            throw new InvalidArgumentException('Payload mal formado.');
        }

        $signatureValid = $this->verifier->verify($payload, $signature);

        $envelopeId = (string) $payloadData['envelopeId'];
        $status = $payloadData['status'] ?? 'unknown';
        $eventType = $payloadData['event'] ?? 'unknown';

        $version = $this->versions->findByEnvelope($envelopeId);

        $this->events->create([
            'envelope_id' => $envelopeId,
            'contract_version_id' => $version?->id(),
            'event_type' => $eventType,
            'status' => is_string($status) ? $status : null,
            'signature_valid' => $signatureValid,
            'payload' => $payloadData,
        ]);

        if ($version === null) {
            return ['ack' => true];
        }

        $updates = ['status' => match ($status) {
            'completed' => 'completed',
            'declined' => 'declined',
            'voided' => 'voided',
            default => $version->status(),
        }];

        if ($status === 'completed') {
            $updates['signed_at'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        }

        $version = $this->versions->update($version->id(), $updates);

        if ($status === 'completed') {
            $versionData = $version->toArray();
            $companyId = isset($versionData['company_id']) && is_string($versionData['company_id'])
                ? $versionData['company_id']
                : ($payloadData['company_id'] ?? null);

            if (is_string($companyId)) {
                $this->contracts->update($version->contractId(), (string) $companyId, [
                    'status' => 'active',
                    'current_version_id' => $version->id(),
                ]);
            }
        }

        $this->envelopes->updateByEnvelopeId($envelopeId, [
            'status' => $status,
            'last_event_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'payload' => $payloadData,
        ]);

        if ($signatureValid && isset($payloadData['recipients']) && is_array($payloadData['recipients'])) {
            foreach ($payloadData['recipients'] as $recipient) {
                if (isset($recipient['email'], $recipient['status'])) {
                    $signer = $this->findSignerByEmail($version->id(), (string) $recipient['email']);
                    if ($signer !== null) {
                        $this->signers->update($signer['id'], [
                            'status' => $recipient['status'],
                            'signed_at' => $recipient['status'] === 'completed' ? (new DateTimeImmutable())->format('Y-m-d H:i:s') : null,
                        ]);
                    }
                }
            }
        }

        return [
            'ack' => true,
            'signature_valid' => $signatureValid,
        ];
    }

    private function findSignerByEmail(string $contractVersionId, string $email): ?array
    {
        $signers = $this->signers->byVersion($contractVersionId);

        foreach ($signers as $signer) {
            $data = $signer->toArray();
            if (strcasecmp($data['email'], $email) === 0) {
                return $data;
            }
        }

        return null;
    }
}
