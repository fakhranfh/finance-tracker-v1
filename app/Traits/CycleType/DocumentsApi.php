<?php

namespace App\Traits\CycleType;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Usage Cycle item type endpoints. See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/cycle-categories/{id}/types',
        summary: 'List the item types of an expense category, paginated and searchable by name',
        description: '`category` (id, name, monthly) describes the whole category, not only this page.',
        tags: ['Usage Cycle'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 12, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'A page of item types in data (id, category_id, name, icon, avg_days, monthly) with links and meta, plus category'),
            new OA\Response(response: 404, description: 'Not found, or not an expense category'),
        ]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/cycle-categories/{id}/types',
        summary: 'Add an item type (e.g. Sabun Mandi) to an expense category',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 40, example: 'Sabun Mandi'),
                    new OA\Property(property: 'icon', type: 'string', example: 'sparkles'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Item type created'),
            new OA\Response(response: 404, description: 'Not found, or not an expense category'),
            new OA\Response(response: 422, description: 'Validation error (the name must be unique within the category)'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Post(
        path: '/cycle-categories/{id}/types/generate',
        summary: 'Generate sample item names with purchase histories in an expense category (local environment only)',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['count'],
                properties: [new OA\Property(property: 'count', type: 'integer', minimum: 1, maximum: 50, example: 3)]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'The generated item names in data'),
            new OA\Response(response: 404, description: 'Not found (outside the local environment, or not an expense category)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentGenerateEndpoint(): void {}

    #[OA\Get(
        path: '/cycle-types/{id}',
        summary: 'Show an item type with its category (the purchases are listed by GET /cycle-types/{id}/entries)',
        description: '`avg_days` is the average number of days a product lasted and `monthly` the cost per month; both are null until a purchase has finished.',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The item type with category, avg_days and monthly'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}
}
