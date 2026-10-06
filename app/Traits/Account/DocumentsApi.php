<?php

namespace App\Traits\Account;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Account endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/accounts',
        summary: 'List accounts',
        tags: ['Accounts'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of accounts')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/accounts',
        summary: 'Create a account',
        tags: ['Accounts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sample name'),
                    new OA\Property(property: 'balance', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'color', type: 'string', example: '#2563EB', description: 'Hex color code, e.g. #2563EB'),
                    new OA\Property(property: 'icon', type: 'string', example: 'wallet', enum: ['cash', 'wallet', 'bank', 'sprout']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Account created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Get(
        path: '/accounts/{id}',
        summary: 'Show a account',
        tags: ['Accounts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The account'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/accounts/{id}',
        summary: 'Update a account',
        tags: ['Accounts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sample name'),
                    new OA\Property(property: 'balance', type: 'number', format: 'float', example: 9.99),
                    new OA\Property(property: 'color', type: 'string', example: '#2563EB', description: 'Hex color code, e.g. #2563EB'),
                    new OA\Property(property: 'icon', type: 'string', example: 'wallet', enum: ['cash', 'wallet', 'bank', 'sprout']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Account updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/accounts/{id}',
        summary: 'Delete a account',
        tags: ['Accounts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Account deleted')]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/accounts',
        summary: 'Bulk delete accounts',
        tags: ['Accounts'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 204, description: 'Accounts deleted')]
    )]
    private function documentBulkDestroyEndpoint(): void {}

    #[OA\Put(
        path: '/accounts/order',
        summary: 'Reorder accounts',
        description: 'Gives the listed accounts the display slots they currently occupy, in the order of `ids`. Accounts not listed keep their positions.',
        tags: ['Accounts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), example: [3, 1]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Order saved'),
            new OA\Response(response: 422, description: 'Validation error, e.g. an unknown account ID'),
        ]
    )]
    private function documentReorderEndpoint(): void {}
}
