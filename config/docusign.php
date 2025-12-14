<?php

declare(strict_types=1);

return [
    'base_uri' => env('DOCUSIGN_BASE_URI', 'https://demo.docusign.net/restapi'),
    'integration_key' => env('DOCUSIGN_INTEGRATION_KEY'),
    'account_id' => env('DOCUSIGN_ACCOUNT_ID'),
    'user_guid' => env('DOCUSIGN_USER_GUID'),
    'webhook_secret' => env('DOCUSIGN_WEBHOOK_SECRET', ''),
];
