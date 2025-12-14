<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers;

use App\Application\Contracts\HandleDocusignWebhookUseCase;
use App\Application\Exceptions\ApplicationException;
use InvalidArgumentException;

class DocusignWebhookController extends Controller
{
    public function __construct(private readonly HandleDocusignWebhookUseCase $handler)
    {
    }

    /**
     * @param array<string, mixed> $request
     */
    public function handle(array $request): string
    {
        try {
            $payload = $request['raw_body'] ?? '';
            $signature = $request['headers']['X-DocuSign-Signature-1'] ?? '';

            $result = ($this->handler)([
                'payload' => (string) $payload,
                'signature' => (string) $signature,
            ]);

            return $this->ok($result);
        } catch (InvalidArgumentException|ApplicationException $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }
}
