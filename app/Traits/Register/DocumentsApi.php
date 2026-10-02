<?php

namespace App\Traits\Register;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Register endpoint.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Post(
        path: '/register',
        summary: 'Register a new user',
        tags: ['Auth'],
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
                description: 'User registered successfully',
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
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The email has already been taken.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    private function documentRegisterEndpoint(): void {}
}
