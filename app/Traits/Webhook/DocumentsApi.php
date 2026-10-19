<?php

namespace App\Traits\Webhook;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Webhook endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/webhooks',
        summary: 'List webhooks',
        tags: ['Webhooks'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of webhooks')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/webhooks',
        summary: 'Create a webhook',
        tags: ['Webhooks'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sample name'),
                    new OA\Property(property: 'allowed_account_ids', type: 'array', nullable: true, items: new OA\Items(type: 'integer'), description: 'Accounts records may be added to. Empty allows every account.'),
                    new OA\Property(property: 'allowed_types', type: 'array', nullable: true, items: new OA\Items(type: 'string', enum: ['income', 'expense', 'transfer']), description: 'Record types that may be added. Empty allows every type.'),
                    new OA\Property(property: 'allowed_ips', type: 'string', nullable: true, example: "203.0.113.5\n198.51.100.0/24", description: 'Whitelisted IP addresses or CIDR ranges, one per line (or comma separated). Empty allows every address.'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Webhook created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Post(
        path: '/webhooks/{id}/test',
        summary: 'Test a webhook',
        description: 'Dry run of POST /webhooks/records for a logged-in user: takes the same signed request (X-Webhook-Timestamp and X-Webhook-Nonce headers, signature in the body) and runs the same checks, but saves nothing and does not use up the nonce.',
        tags: ['Webhooks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Timestamp', in: 'header', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Nonce', in: 'header', required: true, schema: new OA\Schema(type: 'string', minLength: 16, maxLength: 128)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    new OA\Property(property: 'account_name', type: 'string', example: 'Cash'),
                    new OA\Property(property: 'signature', type: 'string', description: 'Same signature as on POST /webhooks/records.'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 12.5),
                    new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'ip', type: 'string', example: '203.0.113.5', description: 'IP address to check against the whitelist (only needed when IPs are whitelisted).'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Payload is valid and the webhook is active'),
            new OA\Response(response: 401, description: 'Missing or invalid signature or headers, or a stale timestamp'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 403, description: 'IP address, record type or account is not allowed for this webhook'),
            new OA\Response(response: 409, description: 'Webhook is inactive'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentTestEndpoint(): void {}

    #[OA\Get(
        path: '/webhooks/{id}',
        summary: 'Show a webhook',
        tags: ['Webhooks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Timestamp', in: 'header', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Nonce', in: 'header', required: true, schema: new OA\Schema(type: 'string', minLength: 16, maxLength: 128)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'The webhook'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/webhooks/{id}',
        summary: 'Update a webhook',
        tags: ['Webhooks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Timestamp', in: 'header', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Nonce', in: 'header', required: true, schema: new OA\Schema(type: 'string', minLength: 16, maxLength: 128)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sample name'),
                    new OA\Property(property: 'allowed_account_ids', type: 'array', nullable: true, items: new OA\Items(type: 'integer'), description: 'Accounts records may be added to. Empty allows every account.'),
                    new OA\Property(property: 'allowed_types', type: 'array', nullable: true, items: new OA\Items(type: 'string', enum: ['income', 'expense', 'transfer']), description: 'Record types that may be added. Empty allows every type.'),
                    new OA\Property(property: 'allowed_ips', type: 'string', nullable: true, example: "203.0.113.5\n198.51.100.0/24", description: 'Whitelisted IP addresses or CIDR ranges, one per line (or comma separated). Empty allows every address.'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Webhook updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/webhooks/{id}',
        summary: 'Delete a webhook',
        tags: ['Webhooks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Timestamp', in: 'header', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Webhook-Nonce', in: 'header', required: true, schema: new OA\Schema(type: 'string', minLength: 16, maxLength: 128)),
        ],
        responses: [new OA\Response(response: 204, description: 'Webhook deleted')]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/webhooks',
        summary: 'Bulk delete webhooks',
        tags: ['Webhooks'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 204, description: 'Webhooks deleted')]
    )]
    private function documentBulkDestroyEndpoint(): void {}
}
