<?php

namespace App\Traits\Category;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Category endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/categories',
        summary: 'List categories',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of categories')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/categories',
        summary: 'Create a category',
        tags: ['Categories'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Groceries'),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense', description: 'Cannot change once records use the category.'),
                    new OA\Property(property: 'color', type: 'string', example: '#2563EB', description: 'Hex color, e.g. #2563EB.'),
                    new OA\Property(property: 'icon', type: 'string', example: 'tag', description: 'One of the keys in config/category.php.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Category created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Get(
        path: '/categories/{id}',
        summary: 'Show a category',
        tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The category'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/categories/{id}',
        summary: 'Update a category',
        tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Groceries'),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense', description: 'Cannot change once records use the category.'),
                    new OA\Property(property: 'color', type: 'string', example: '#2563EB', description: 'Hex color, e.g. #2563EB.'),
                    new OA\Property(property: 'icon', type: 'string', example: 'tag', description: 'One of the keys in config/category.php.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Category updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/categories/{id}',
        summary: 'Delete a category',
        description: 'Its records are kept, uncategorised.',
        tags: ['Categories'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Category deleted')]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/categories',
        summary: 'Bulk delete categories',
        tags: ['Categories'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 204, description: 'Categories deleted')]
    )]
    private function documentBulkDestroyEndpoint(): void {}

    #[OA\Post(
        path: '/categories/generate',
        summary: 'Generate sample categories (local environment only)',
        tags: ['Categories'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'count', type: 'integer', minimum: 1, maximum: 50, example: 10)]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'The generated categories'),
            new OA\Response(response: 404, description: 'Not available outside the local environment'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentGenerateEndpoint(): void {}
}
