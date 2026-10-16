<?php

namespace App\Traits\WebhookLog;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Webhook log endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/webhooks/{id}/logs',
        summary: 'List webhook logs',
        description: 'Every call to the webhook public endpoint (status, origin, IP, payload, created record), newest first. The latest 500 are kept.',
        tags: ['Webhooks'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['status_code', 'created_at'])),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of logs'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Delete(
        path: '/webhooks/{id}/logs',
        summary: 'Clear webhook logs',
        tags: ['Webhooks'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Logs cleared'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentDestroyEndpoint(): void {}
}
