<?php

namespace App\Traits\Receivable;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Receivable endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/receivables',
        summary: 'List receivables',
        tags: ['Receivables'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of receivables')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/receivables',
        summary: 'Create a receivable',
        tags: ['Receivables'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sample name'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2026-01-15'),
                    new OA\Property(property: 'notes', type: 'string', example: 'Sample notes'),
                    new OA\Property(property: 'is_paid', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Receivable created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Get(
        path: '/receivables/{id}',
        summary: 'Show a receivable',
        tags: ['Receivables'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The receivable'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/receivables/{id}',
        summary: 'Update a receivable',
        tags: ['Receivables'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sample name'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2026-01-15'),
                    new OA\Property(property: 'notes', type: 'string', example: 'Sample notes'),
                    new OA\Property(property: 'is_paid', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Receivable updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/receivables/{id}',
        summary: 'Delete a receivable',
        tags: ['Receivables'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Receivable deleted')]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/receivables',
        summary: 'Bulk delete receivables',
        tags: ['Receivables'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 204, description: 'Receivables deleted')]
    )]
    private function documentBulkDestroyEndpoint(): void {}
}
