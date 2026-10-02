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
abstract class Controller
{
    //
}
