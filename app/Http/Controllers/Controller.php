<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Laravel 13 Boilerplate API',
    description: 'Session-based JSON API covering the application\'s auth flows (Register, Login, Logout, Forgot Password, Reset Password).'
)]
#[OA\Server(
    url: '/api',
    description: 'API server'
)]
#[OA\Tag(name: 'Auth', description: 'Authentication endpoints')]
#[OA\Tag(name: 'Profile', description: 'Authenticated user profile endpoints')]
#[OA\Tag(name: 'Terminal', description: 'Admin-only web terminal for running shell commands')]
#[OA\Tag(name: 'Accounts', description: 'Account endpoints')]
#[OA\Tag(name: 'Categories', description: 'Record category endpoints')]
#[OA\Tag(name: 'Webhooks', description: 'Webhook management and public record intake endpoints')]
#[OA\Tag(name: 'Debt Payments', description: 'Records that pay a debt or receivable off')]
#[OA\Tag(name: 'Sub Debts', description: 'Sub debt endpoints (the instalments of a debt or receivable)')]
#[OA\Tag(name: 'Sub Goals', description: 'Sub goal endpoints (the steps needed to reach a goal)')]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
        new OA\Property(property: 'timezone', type: 'string', example: 'Asia/Jakarta'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
abstract class Controller
{
    //
}
