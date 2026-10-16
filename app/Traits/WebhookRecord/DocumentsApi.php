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
        description: 'Public endpoint authenticated by the webhook token in the X-Webhook-Token header. Accepts the same payload as POST /records. A missing or unknown token returns 401 and an inactive webhook 404.',
        tags: ['Webhooks'],
        parameters: [new OA\Parameter(name: 'X-Webhook-Token', in: 'header', required: true, description: 'The webhook token.', schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense', 'transfer'], example: 'expense'),
                    new OA\Property(property: 'account_name', type: 'string', example: 'Cash', description: 'Name of the source account.'),
                    new OA\Property(property: 'to_account_id', type: 'integer', nullable: true, description: 'Required for transfers, ignored otherwise.'),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Coffee'),
                    new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time', example: '2026-01-15T10:30:00+07:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Record created'),
            new OA\Response(response: 403, description: 'The caller IP address is not in the webhook whitelist'),
            new OA\Response(response: 401, description: 'Missing or unknown webhook token'),
            new OA\Response(response: 404, description: 'Inactive webhook'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    private function documentStoreEndpoint(): void {}
}
