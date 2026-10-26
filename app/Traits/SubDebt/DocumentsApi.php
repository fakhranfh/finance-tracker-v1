<?php

namespace App\Traits\SubDebt;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Sub debt endpoints (the instalments of a debt or receivable).
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/debts/{id}/sub-debts',
        summary: 'List the sub debts of a debt or receivable (they are created and edited through the debt endpoints)',
        tags: ['Sub Debts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['name', 'amount', 'paid_amount', 'due_date', 'is_paid', 'note', 'created_at'])),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated sub debts in data, plus summary (total, paid, amount_total, paid_total of all the debt sub debts)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/debts/{id}/sub-debts',
        summary: 'Create a sub debt; the debt amount follows the sub debts',
        tags: ['Sub Debts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Cicilan bulan ini'),
                    new OA\Property(property: 'amount', type: 'integer', example: 150000),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'note', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Sub debt created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Delete(
        path: '/debts/{id}/sub-debts',
        summary: 'Delete several sub debts of a debt (by ids, or every one matching the search when all is true)',
        tags: ['Sub Debts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean', example: false),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Sub debts deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentBulkDestroyEndpoint(): void {}

    #[OA\Post(
        path: '/debts/{id}/sub-debts/generate',
        summary: 'Generate sample sub debts for a debt (local environment only)',
        tags: ['Sub Debts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'count', type: 'integer', example: 5)])
        ),
        responses: [
            new OA\Response(response: 200, description: 'The generated sub debts'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found, or not the local environment'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentGenerateEndpoint(): void {}

    #[OA\Delete(
        path: '/debts/{id}/sub-debts/{subDebtId}',
        summary: 'Delete a sub debt',
        tags: ['Sub Debts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'subDebtId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Sub debt deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Put(
        path: '/debts/{id}/sub-debts',
        summary: 'Replace the sub debts of a debt or receivable',
        description: 'Items with an id are updated, items without one are created and the sub debts left out are removed. A non-empty list also sets the debt amount from the sub debts; an empty list removes them all and keeps the debt amount. What was paid comes from the records linked as payments.',
        tags: ['Sub Debts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'sub_debts',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', nullable: true),
                            new OA\Property(property: 'name', type: 'string', example: 'Cicilan bulan ini'),
                            new OA\Property(property: 'amount', type: 'integer', example: 150000),
                            new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
                            new OA\Property(property: 'note', type: 'string', nullable: true),
                        ])
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'The sub debts in data, plus summary'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentSyncEndpoint(): void {}

    #[OA\Get(
        path: '/debts/{id}/sub-debts/{subDebtId}',
        summary: 'Show a sub debt',
        tags: ['Sub Debts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'subDebtId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'The sub debt'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/debts/{id}/sub-debts/{subDebtId}',
        summary: 'Update a sub debt; the debt amount follows the sub debts',
        description: 'Every field is optional. What was paid comes from the records linked as payments.',
        tags: ['Sub Debts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'subDebtId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Cicilan bulan ini'),
                    new OA\Property(property: 'amount', type: 'integer', example: 150000),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'note', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Sub debt updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}
}
