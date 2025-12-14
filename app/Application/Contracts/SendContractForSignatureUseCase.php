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
use App\Infrastructure\Signing\DocusignClient;

class SendContractForSignatureUseCase implements UseCase
{
    public function __construct(
        private readonly ContractRepositoryInterface $contracts,
        private readonly ContractVersionRepositoryInterface $versions,
        private readonly ContractSignerRepositoryInterface $signers,
        private readonly DocusignEnvelopeRepositoryInterface $envelopes,
        private readonly DocusignClient $client,
    ) {
    }

    /**
     * @param array{company_id:string,contract_id:string,version_id?:string|null,signers:array<int,array{name:string,email:string,role?:string|null}>} $input
     */
    public function __invoke(mixed $input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('Payload inválido para firma.');
        }

        if (empty($input['company_id']) || empty($input['contract_id']) || ! isset($input['signers']) || ! is_array($input['signers'])) {
            throw new InvalidArgumentException('company_id, contract_id y signers son obligatorios');
        }

        $contract = $this->contracts->findById((string) $input['contract_id'], (string) $input['company_id']);
        if ($contract === null) {
            throw new InvalidArgumentException('Contrato no encontrado.');
        }

        $versionId = $input['version_id'] ?? $contract->toArray()['current_version_id'];
        if (! $versionId) {
            throw new InvalidArgumentException('No hay versión activa para enviar.');
        }

        $version = $this->versions->findById((string) $versionId);
        if ($version === null) {
            throw new InvalidArgumentException('Versión no encontrada.');
        }

        $payloadSigners = [];
        foreach ($input['signers'] as $signer) {
            if (! isset($signer['name'], $signer['email'])) {
                throw new InvalidArgumentException('Cada firmante requiere nombre y email.');
            }

            $payloadSigners[] = [
                'name' => (string) $signer['name'],
                'email' => (string) $signer['email'],
                'role' => $signer['role'] ?? 'counterparty',
            ];
        }

        $webhookKey = bin2hex(random_bytes(16));
        $envelope = $this->client->createEnvelope(
            $contract->toArray()['title'] ?? 'Contrato',
            $version->toArray()['body_snapshot'],
            $payloadSigners,
            $webhookKey,
        );

        $this->signers->replaceForVersion((string) $versionId, $payloadSigners);

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $version = $this->versions->update((string) $versionId, [
            'docusign_envelope_id' => $envelope['envelope_id'],
            'status' => 'pending_signature',
            'sent_at' => $now,
        ]);

        $contract = $this->contracts->update($contract->id(), $contract->companyId(), [
            'status' => 'pending_signature',
            'current_version_id' => $version->id(),
        ]);

        $this->envelopes->create([
            'contract_id' => $contract->id(),
            'contract_version_id' => $version->id(),
            'envelope_id' => $envelope['envelope_id'],
            'status' => $envelope['status'],
            'last_event_at' => $now,
            'webhook_key' => $webhookKey,
            'payload' => [
                'subject' => $contract->toArray()['title'] ?? 'Contrato',
            ],
        ]);

        return [
            'contract' => $contract->toArray(),
            'version' => $version->toArray(),
            'envelope' => $envelope,
        ];
    }
}
