<?php

namespace App\Traits\CycleEntry;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Usage Cycle purchase endpoints. See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/cycle-types/{id}/entries',
        summary: 'List the purchases of an item type, oldest first, paginated and searchable by product name',
        description: '`type` describes the whole item type: avg_days, monthly and last_entry (name and price of the latest purchase), worked out from every purchase, not only this page.',
        tags: ['Usage Cycle'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'A page of purchases in data with links and meta, plus type'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/cycle-types/{id}/entries/generate',
        summary: 'Generate sample purchases for an item name: one product bought again and again, the newest still in use (local environment only)',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['count'],
                properties: [new OA\Property(property: 'count', type: 'integer', minimum: 1, maximum: 50, example: 6)]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'The generated purchases in data'),
            new OA\Response(response: 404, description: 'Not found (outside the local environment)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentGenerateEndpoint(): void {}

    #[OA\Post(
        path: '/cycle-types/{id}/entries',
        summary: 'Log a purchase of an item type',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'bought_at'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 80, example: 'Shinzui'),
                    new OA\Property(property: 'price', type: 'integer', example: 35000),
                    new OA\Property(property: 'bought_at', type: 'string', format: 'date', example: '2026-10-08'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Purchase created'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Delete(
        path: '/cycle-types/{id}/entries',
        summary: 'Delete several purchases of an item type at once',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Purchases deleted'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error (every id must be a purchase of this item type)'),
        ]
    )]
    private function documentBulkDestroyEndpoint(): void {}

    #[OA\Get(
        path: '/cycle-entries/{id}',
        summary: 'Show a purchase with its item type (including the average days a product of that type lasts)',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The purchase, with type (id, name, category_id, avg_days)'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/cycle-entries/{id}',
        summary: 'Update a purchase (product name, price and date bought)',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'bought_at'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 80, example: 'Shinzui'),
                    new OA\Property(property: 'price', type: 'integer', example: 35000),
                    new OA\Property(property: 'bought_at', type: 'string', format: 'date', description: 'Not after the date it ran out', example: '2026-10-08'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Purchase updated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Put(
        path: '/cycle-entries/{id}/finish',
        summary: 'Mark a purchase as run out',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['finished_at'],
                properties: [new OA\Property(property: 'finished_at', type: 'string', format: 'date', description: 'Not before the date it was bought', example: '2026-11-05')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Purchase marked as finished'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentFinishEndpoint(): void {}

    #[OA\Delete(
        path: '/cycle-entries/{id}',
        summary: 'Delete a purchase',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Purchase deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentDestroyEndpoint(): void {}
}
