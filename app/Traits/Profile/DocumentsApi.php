<?php

namespace App\Traits\Profile;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the authenticated user's profile endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/profile',
        summary: 'Show the authenticated user\'s profile',
        tags: ['Profile'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    private function documentProfileShowEndpoint(): void {}

    #[OA\Put(
        path: '/profile',
        summary: 'Update the authenticated user\'s profile',
        tags: ['Profile'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'jane@example.com'),
                    new OA\Property(property: 'current_password', type: 'string', format: 'password', nullable: true, description: 'Required when changing the password.'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true, description: 'Leave empty to keep the current password.'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function documentProfileUpdateEndpoint(): void {}
}
