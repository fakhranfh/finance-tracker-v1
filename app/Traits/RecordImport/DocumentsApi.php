<?php

namespace App\Traits\RecordImport;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the record import endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Post(
        path: '/records/import/preview',
        summary: 'Upload a spreadsheet to import records from',
        description: 'Accepts an xlsx, xls or csv file (max 5 MB, 5000 rows; the first row is the header). Returns a token for the next steps, the columns, a few sample rows and the column each record field most likely comes from.',
        tags: ['Records'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token, columns, sample rows and suggested mapping'),
            new OA\Response(response: 422, description: 'Not a readable spreadsheet, empty, or too large'),
        ]
    )]
    private function documentPreviewEndpoint(): void {}

    #[OA\Post(
        path: '/records/import/values',
        summary: 'List the accounts and categories found in the mapped columns',
        description: 'Maps record fields to column indexes (recorded_at and amount are required). Returns each distinct account and category value of the file with the existing one it most likely stands for, and the existing accounts and categories to choose from.',
        tags: ['Records'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: '3f2b8c1e-0000-4000-8000-000000000000.xlsx'),
                    new OA\Property(
                        property: 'mapping',
                        type: 'object',
                        example: ['recorded_at' => 0, 'type' => 1, 'account' => 2, 'to_account' => 3, 'category' => 4, 'amount' => 5, 'note' => 6]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Distinct accounts and categories with suggestions'),
            new OA\Response(response: 422, description: 'Validation error or expired upload'),
        ]
    )]
    private function documentValuesEndpoint(): void {}

    #[OA\Post(
        path: '/records/import',
        summary: 'Import the uploaded spreadsheet as records',
        description: 'Accounts are mapped to an existing account ("existing" with id), created ("create", named after the value unless a name is given) or sent to the "Unmapped" account ("unmapped"); rows without an account also go there. Categories are mapped to an existing category ("existing" with id), created ("create", named after the value unless a name is given, one per record type) or left uncategorized ("none"); an existing category of another type than the record is swapped for the same-named one of the record type, created if missing. Invalid rows are skipped and reported. Without a type column the amount sign decides: negative is an expense.',
        tags: ['Records'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'mapping', type: 'object'),
                    new OA\Property(
                        property: 'accounts',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'value', type: 'string', example: 'BCA'),
                            new OA\Property(property: 'action', type: 'string', enum: ['existing', 'create', 'unmapped']),
                            new OA\Property(property: 'id', type: 'integer', nullable: true),
                            new OA\Property(property: 'name', type: 'string', nullable: true),
                        ], type: 'object')
                    ),
                    new OA\Property(
                        property: 'categories',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'value', type: 'string', example: 'Food'),
                            new OA\Property(property: 'action', type: 'string', enum: ['existing', 'create', 'none']),
                            new OA\Property(property: 'id', type: 'integer', nullable: true),
                            new OA\Property(property: 'name', type: 'string', nullable: true),
                        ], type: 'object')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Imported and skipped counts, skipped rows with reasons, created accounts'),
            new OA\Response(response: 422, description: 'Validation error or expired upload'),
        ]
    )]
    private function documentStoreEndpoint(): void {}
}
