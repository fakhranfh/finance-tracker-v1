<?php

namespace App\Traits\User;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the User endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/users',
        summary: 'List users',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of users')]
    )]
    private function documentIndexEndpoint(): void {}

    #[OA\Post(
        path: '/users',
        summary: 'Create a user',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sample name'),
                    new OA\Property(property: 'email', type: 'string', example: 'Sample email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentStoreEndpoint(): void {}

    #[OA\Get(
        path: '/users/{id}',
        summary: 'Show a user',
        tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The user'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    private function documentShowEndpoint(): void {}

    #[OA\Put(
        path: '/users/{id}',
        summary: 'Update a user',
        tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Sample name'),
                    new OA\Property(property: 'email', type: 'string', example: 'Sample email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Delete a user',
        tags: ['Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'User deleted')]
    )]
    private function documentDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/users',
        summary: 'Bulk delete users',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'all', type: 'boolean'),
                    new OA\Property(property: 'search', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 204, description: 'Users deleted')]
    )]
    private function documentBulkDestroyEndpoint(): void {}
}
