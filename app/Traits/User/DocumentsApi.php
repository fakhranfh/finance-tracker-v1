<?php

namespace App\Traits\User;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the User management endpoints.
 * See .ai/rules/api-docs.md.
 */
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
        new OA\Property(property: 'timezone', type: 'string', nullable: true, example: 'Asia/Jakarta'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
trait DocumentsApi
{
    #[OA\Get(
        path: '/users',
        summary: 'List users (paginated)',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100, minimum: 1)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'direction', in: 'query', schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of users',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
                    new OA\Property(property: 'links', type: 'object'),
                    new OA\Property(property: 'meta', type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    private function documentUserIndexEndpoint(): void {}

    #[OA\Post(
        path: '/users',
        summary: 'Create a user',
        tags: ['Users'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'Password123!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUserStoreEndpoint(): void {}

    #[OA\Get(
        path: '/users/{user}',
        summary: 'Show a user',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User details',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    private function documentUserShowEndpoint(): void {}

    #[OA\Put(
        path: '/users/{user}',
        summary: 'Update a user',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true, description: 'Leave empty to keep the current password.'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUserUpdateEndpoint(): void {}

    #[OA\Delete(
        path: '/users/{user}',
        summary: 'Delete a user',
        description: 'The authenticated user cannot delete themselves.',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'User deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Cannot delete yourself'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    private function documentUserDestroyEndpoint(): void {}

    #[OA\Delete(
        path: '/users',
        summary: 'Bulk delete users',
        description: 'Delete by `ids`, or set `all` to delete every user matching `search`. The authenticated user is always excluded.',
        tags: ['Users'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), example: [2, 3]),
                new OA\Property(property: 'all', type: 'boolean', example: false),
                new OA\Property(property: 'search', type: 'string', nullable: true),
            ])
        ),
        responses: [
            new OA\Response(response: 204, description: 'Users deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUserBulkDestroyEndpoint(): void {}

    #[OA\Post(
        path: '/users/generate',
        summary: 'Generate sample users (local environment only)',
        tags: ['Users'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['count'],
                properties: [
                    new OA\Property(property: 'count', type: 'integer', maximum: 50, minimum: 1, example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Generated sample users',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not available outside local environment'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentUserGenerateEndpoint(): void {}
}
