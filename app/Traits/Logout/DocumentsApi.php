<?php

namespace App\Traits\Logout;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the Logout endpoint.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Post(
        path: '/logout',
        summary: 'Log the authenticated user out and revoke their tokens',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'You have been logged out.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    private function documentLogoutEndpoint(): void {}
}
