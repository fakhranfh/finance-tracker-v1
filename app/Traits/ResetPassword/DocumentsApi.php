<?php

namespace App\Traits\ResetPassword;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Reset Password endpoint.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Post(
        path: '/reset-password',
        summary: 'Reset a user\'s password using a valid reset token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'a1b2c3...'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewPassword123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewPassword123!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Your password has been reset.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error or invalid/expired token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This password reset token is invalid.'),
                    ]
                )
            ),
        ]
    )]
    private function documentResetPasswordEndpoint(): void {}
}
