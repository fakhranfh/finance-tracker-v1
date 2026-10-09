<?php

namespace App\Traits\Record;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Record endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/records',
        summary: 'List records',
        tags: ['Records'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of records')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Get(
        path: '/records/summary',
        summary: 'Summarize records',
        description: 'Income, expense, transfers in and out, net and record count (computed like the account summary: stored account totals when only accounts and a date range filter, the records otherwise) for the records matching the search and filters.',
        tags: ['Records'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type[]', in: 'query', required: false, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string', enum: ['income', 'expense', 'transfer']))),
            new OA\Parameter(name: 'account_ids[]', in: 'query', required: false, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer'))),
            new OA\Parameter(name: 'category_ids[]', in: 'query', required: false, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'amount_min', in: 'query', required: false, schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'amount_max', in: 'query', required: false, schema: new OA\Schema(type: 'number')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Totals of the matching records'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentSummaryEndpoint(): void {}

    #[OA\Post(
        path: '/records',
        summary: 'Create a record',
        tags: ['Records'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense', 'transfer'], example: 'expense'),
                    new OA\Property(property: 'account_id', type: 'integer', example: 1, description: 'Source account for transfers.'),
                    new OA\Property(property: 'to_account_id', type: 'integer', nullable: true, description: 'Destination account; required for transfers, ignored otherwise.'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'note', type: 'string', example: 'Sample note'),
                    new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time', example: '2026-01-15T10:30:00+07:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Record created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Get(
        path: '/records/{id}',
        summary: 'Show a record',
        tags: ['Records'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The record'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/records/{id}',
        summary: 'Update a record',
        tags: ['Records'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense', 'transfer'], example: 'expense'),
                    new OA\Property(property: 'account_id', type: 'integer', example: 1, description: 'Source account for transfers.'),
                    new OA\Property(property: 'to_account_id', type: 'integer', nullable: true, description: 'Destination account; required for transfers, ignored otherwise.'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'note', type: 'string', example: 'Sample note'),
                    new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time', example: '2026-01-15T10:30:00+07:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Record updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/records/{id}',
        summary: 'Delete a record',
        tags: ['Records'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Record deleted')]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/records',
        summary: 'Bulk delete records',
        tags: ['Records'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 204, description: 'Records deleted')]
    )]
    private function documentBulkDestroyEndpoint(): void {}
}
