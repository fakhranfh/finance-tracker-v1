<?php

namespace App\Traits\Terminal;

use OpenApi\Attributes as OA;

/**
 * OpenAPI/Swagger annotations for the admin web terminal endpoints.
 * See .ai/rules/api-docs.md.
 */
trait DocumentsApi
{
    #[OA\Get(
        path: '/terminal',
        summary: 'Show whether a terminal password is set and unlocked for this session',
        tags: ['Terminal'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Terminal state',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'configured', type: 'boolean', description: 'False until an admin has set a terminal password.'),
                        new OA\Property(property: 'unlocked', type: 'boolean'),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
        ]
    )]
    private function documentTerminalShowEndpoint(): void {}

    #[OA\Post(
        path: '/terminal/unlock',
        summary: 'Unlock the terminal with the terminal password',
        tags: ['Terminal'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password'],
                properties: [
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Terminal unlocked for this session'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
            new OA\Response(response: 422, description: 'Wrong password, or no terminal password is set yet'),
            new OA\Response(response: 429, description: 'Too many attempts'),
        ]
    )]
    private function documentTerminalUnlockEndpoint(): void {}

    #[OA\Delete(
        path: '/terminal',
        summary: 'Lock the terminal again',
        tags: ['Terminal'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Terminal locked'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
        ]
    )]
    private function documentTerminalLockEndpoint(): void {}

    #[OA\Post(
        path: '/terminal/run',
        summary: 'Run a command through bash in the project root (e.g. "php artisan migrate:status" or "git status")',
        tags: ['Terminal'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['command'],
                properties: [
                    new OA\Property(property: 'command', type: 'string', maxLength: 1000, example: 'php artisan migrate:status'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Command finished (a non-zero exit_code means it failed)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'command', type: 'string'),
                        new OA\Property(property: 'output', type: 'string'),
                        new OA\Property(property: 'exit_code', type: 'integer'),
                    ], type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
            new OA\Response(response: 422, description: 'Missing command'),
            new OA\Response(response: 423, description: 'Terminal is locked'),
        ]
    )]
    private function documentTerminalRunEndpoint(): void {}

    #[OA\Put(
        path: '/terminal/password',
        summary: 'Set or change the terminal password (stored hashed in the database)',
        tags: ['Terminal'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password', description: 'Required once a password is already set.'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password saved; the terminal is unlocked for this session'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
            new OA\Response(response: 422, description: 'Validation error or wrong current password'),
        ]
    )]
    private function documentTerminalPasswordEndpoint(): void {}

    #[OA\Post(
        path: '/terminal/forgot',
        summary: 'Email the signed-in admin a link to reset the terminal password',
        tags: ['Terminal'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Reset link emailed (valid for 60 minutes)'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
            new OA\Response(response: 422, description: 'No terminal password is set yet'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    private function documentTerminalForgotEndpoint(): void {}

    #[OA\Post(
        path: '/terminal/reset',
        summary: 'Set a new terminal password with the token from the reset email',
        tags: ['Terminal'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset; the terminal is unlocked for this session'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Not an admin'),
            new OA\Response(response: 422, description: 'Validation error, or the token is invalid or expired'),
            new OA\Response(response: 429, description: 'Too many attempts'),
        ]
    )]
    private function documentTerminalResetEndpoint(): void {}
}
