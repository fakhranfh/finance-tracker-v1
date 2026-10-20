<?php

namespace App\Traits\WebhookRecord;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the public webhook record intake endpoint.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Post(
        path: '/webhooks/records',
        summary: 'Add a record through a webhook',
        description: 'Public endpoint. The body carries the record fields and a `signature`; the X-Webhook-Timestamp and X-Webhook-Nonce headers are signed with them. The webhook is the one whose token reproduces the signature. Only income and expense records are accepted. A missing or invalid signature, a stale timestamp, or a reused nonce returns 401 and an inactive webhook 404.',
        tags: ['Webhooks'],
        parameters: [
            new OA\Parameter(name: 'X-Webhook-Timestamp', in: 'header', required: true, description: 'Unix time (seconds) the request was signed at. Must be within 5 minutes of the server time.', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Nonce', in: 'header', required: true, description: 'A unique value (16-128 characters) per request. Each nonce is accepted once, so a replayed request is rejected with 401.', schema: new OA\Schema(type: 'string', minLength: 16, maxLength: 128)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'account_name', 'amount', 'recorded_at', 'signature'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    new OA\Property(property: 'account_name', type: 'string', example: 'Cash', description: 'Name of the source account. An unknown name is rejected unless the webhook may create accounts.'),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'amount', type: 'integer', example: 10),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Coffee'),
                    new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time', example: '2026-01-15T10:30:00+07:00'),
                    new OA\Property(property: 'signature', type: 'string', description: 'The hex HMAC-SHA256, keyed by the webhook token, of "timestamp=..&nonce=..&type=..&account_name=..&category_id=..&amount=..&note=..&recorded_at=.." (timestamp and nonce are the header values; missing record fields are empty).'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Record created'),
            new OA\Response(response: 403, description: 'The caller IP address, the record type or the account is not allowed for this webhook'),
            new OA\Response(response: 401, description: 'Missing or invalid signature or headers, a stale timestamp, or a reused nonce'),
            new OA\Response(response: 404, description: 'Inactive webhook'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    private function documentStoreEndpoint(): void {}
}
