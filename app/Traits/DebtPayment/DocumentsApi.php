<?php

namespace App\Traits\DebtPayment;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the debt payment endpoints (records that pay a debt or receivable off).
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/debts/{id}/payments',
        summary: 'List the payments (linked records) of a debt or receivable, or of one of its sub debts',
        tags: ['Debt Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sub_debt_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['amount', 'note', 'recorded_at', 'created_at'])),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated payments in data, plus summary (amount, paid, remaining of the debt)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Get(
        path: '/debts/{id}/payments/available',
        summary: 'List existing records that could pay the debt off (matching type, not linked to a debt yet)',
        description: 'A debt is repaid with expense records and a receivable settled with income records.',
        tags: ['Debt Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['amount', 'note', 'recorded_at', 'created_at'])),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated candidate records'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentAvailableEndpoint(): void {}

    #[OA\Post(
        path: '/debts/{id}/payments',
        summary: 'Pay a debt or receivable off with an existing record (record_id) or a new record',
        description: 'The new record is an expense for a debt and an income for a receivable. A debt split into sub debts is paid per sub debt (sub_debt_id is required). A payment cannot exceed what is still owed.',
        tags: ['Debt Payments'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'record_id', type: 'integer', nullable: true, description: 'Use this existing record; the fields below are then ignored'),
                    new OA\Property(property: 'sub_debt_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'account_id', type: 'integer', example: 1),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'amount', type: 'integer', example: 50000),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Cicilan bulan ini'),
                    new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'The payment'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Delete(
        path: '/debts/{id}/payments/{recordId}',
        summary: 'Stop a record from paying the debt off (the record itself is kept)',
        tags: ['Debt Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'recordId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Payment removed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found, or the record does not pay this debt'),
        ]
    )]
    private function documentDestroyEndpoint(): void {}
}
