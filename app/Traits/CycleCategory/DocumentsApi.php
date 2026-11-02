<?php

namespace App\Traits\CycleCategory;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Usage Cycle category endpoints (the expense categories and what they cost per month).
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/cycle-categories',
        summary: 'List the expense categories with the monthly cost of their item types, paginated and searchable by name',
        description: 'Categories are created and edited through the category endpoints. `monthly` is the sum of the monthly cost of the category item types (0 until a purchase has finished). `summary.monthly_total` covers all categories, not only the page or the search.',
        tags: ['Usage Cycle'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 12, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'A page of expense categories in data (id, name, color, icon, monthly) with links and meta, plus summary.monthly_total'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/cycle-categories/generate',
        summary: 'Generate sample expense categories with item names and purchase histories (local environment only)',
        tags: ['Usage Cycle'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['count'],
                properties: [new OA\Property(property: 'count', type: 'integer', minimum: 1, maximum: 50, example: 3)]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'The generated categories in data'),
            new OA\Response(response: 404, description: 'Not found (outside the local environment)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentGenerateEndpoint(): void {}

    #[OA\Get(
        path: '/cycle-categories/{id}',
        summary: 'Show an expense category with its monthly cost (the item types are listed by GET /cycle-categories/{id}/types)',
        tags: ['Usage Cycle'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The category (id, name, color, icon, monthly)'),
            new OA\Response(response: 404, description: 'Not found, or not an expense category'),
        ]
    )]
    private function documentShowEndpoint(): void {}
}
