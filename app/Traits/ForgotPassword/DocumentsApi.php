<?php

namespace App\Traits\ForgotPassword;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Forgot Password endpoint.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Post(
        path: '/forgot-password',
        summary: 'Send a password reset link to the given email address',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reset link sent',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'We have emailed your password reset link.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error or no matching account',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'We can\'t find a user with that email address.'),
                    ]
                )
            ),
        ]
    )]
    private function documentForgotPasswordEndpoint(): void {}
}
