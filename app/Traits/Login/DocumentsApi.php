<?php

namespace App\Traits\Login;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Login endpoint.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Post(
        path: '/login',
        summary: 'Authenticate a user and issue an API token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
                    new OA\Property(property: 'remember', type: 'boolean', example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                            new OA\Property(property: 'email', type: 'string', example: 'jane@example.com'),
                        ], type: 'object'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456...'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid credentials or validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'These credentials do not match our records.'),
                    ]
                )
            ),
        ]
    )]
    private function documentLoginEndpoint(): void {}
}
