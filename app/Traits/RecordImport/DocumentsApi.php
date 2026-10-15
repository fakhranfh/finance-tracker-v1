<?php

namespace App\Traits\RecordImport;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the record import endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/records/imports',
        summary: 'List unfinished record imports',
        description: 'The current user\'s uploads that have not been imported or discarded, most recently worked on first. Any number can be open at once.',
        tags: ['Records'],
        responses: [new OA\Response(response: 200, description: 'Unfinished imports (id, filename, step, row_count, updated_at)')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/records/imports',
        summary: 'Upload a spreadsheet to import records from',
        description: 'Accepts an xlsx, xls or csv file (max 5 MB, 5000 rows; the first row is the header) and starts a new import at the columns step. The response holds the columns, a few sample rows and the mapping the column names suggest.',
        tags: ['Records'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'The new import'),
            new OA\Response(response: 422, description: 'Not a readable spreadsheet, empty, or too large'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Get(
        path: '/records/imports/{id}',
        summary: 'Show an unfinished import with its saved progress',
        tags: ['Records'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Step, columns, sample, mapping, values and choices'),
            new OA\Response(response: 404, description: 'Not found, or not the user\'s import'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/records/imports/{id}',
        summary: 'Save the progress of an import',
        description: 'Any of step (columns or values; values only once the values are loaded), mapping (record field to column index, or null) and choices (what was picked for each account and category).',
        tags: ['Records'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'step', type: 'string', enum: ['columns', 'values']),
                    new OA\Property(property: 'mapping', type: 'object', example: ['recorded_at' => 0, 'amount' => 5, 'note' => null]),
                    new OA\Property(property: 'choices', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'The import'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Post(
        path: '/records/imports/{id}/values',
        summary: 'Map the columns and list the accounts and categories found in them',
        description: 'Stores the mapping (recorded_at and amount are required), then returns each distinct account and category value of the file with the existing one it most likely stands for, plus the existing accounts and categories to choose from, and moves the import to the values step.',
        tags: ['Records'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'mapping',
                        type: 'object',
                        example: ['recorded_at' => 0, 'type' => 1, 'account' => 2, 'to_account' => 3, 'category' => 4, 'amount' => 5, 'note' => 6]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'The import with its values'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error or the file is gone'),
        ]
    )]
    private function documentValuesEndpoint(): void {}

    #[OA\Post(
        path: '/records/imports/{id}/run',
        summary: 'Import the uploaded spreadsheet as records',
        description: 'Accounts are mapped to an existing account ("existing" with id), created ("create", named after the value unless a name is given) or sent to the "Unmapped" account ("unmapped"); rows without an account also go there. Categories are mapped to an existing category, created (one per record type) or left uncategorized ("none"); an existing category of another type than the record is swapped for the same-named one of the record type, created if missing. Invalid rows are skipped and reported. Without a type column the amount sign decides: negative is an expense. The import is removed afterwards.',
        tags: ['Records'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'types',
                        type: 'object',
                        description: 'The values of the type column that mean each record type, separated by commas. Values left out are recognized by name (income, expense, transfer, pemasukan, pengeluaran).',
                        example: ['income' => 'Pemasukan, Masuk', 'expense' => 'Pengeluaran', 'transfer' => 'Pindah']
                    ),
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
            new OA\Response(response: 200, description: 'Imported and skipped counts, skipped rows with reasons, created accounts and categories'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error or the file is gone'),
        ]
    )]
    private function documentRunEndpoint(): void {}

    #[OA\Delete(
        path: '/records/imports/{id}',
        summary: 'Discard an unfinished import and its file',
        tags: ['Records'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Import discarded'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentDestroyEndpoint(): void {}
}
