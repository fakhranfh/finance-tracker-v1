<?php

namespace App\Traits\Debt;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Debt endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/debts',
        summary: 'List debts',
        tags: ['Debts'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['debt', 'receivable'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of debts')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/debts',
        summary: 'Create a debt',
        tags: ['Debts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['debt', 'receivable'], example: 'debt'),
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
            new OA\Response(response: 201, description: 'Debt created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Get(
        path: '/debts/{id}',
        summary: 'Show a debt',
        tags: ['Debts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The debt'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/debts/{id}',
        summary: 'Update a debt',
        tags: ['Debts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['debt', 'receivable'], example: 'debt'),
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
            new OA\Response(response: 200, description: 'Debt updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/debts/{id}',
        summary: 'Delete a debt',
        tags: ['Debts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Debt deleted')]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/debts',
        summary: 'Bulk delete debts',
        tags: ['Debts'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 204, description: 'Debts deleted')]
    )]
    private function documentBulkDestroyEndpoint(): void {}
}
