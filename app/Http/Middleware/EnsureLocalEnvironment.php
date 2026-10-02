<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Blocks a route outside the local environment (e.g. Swagger UI/docs).
 * See .ai/rules/api-docs.md.
 */
class EnsureLocalEnvironment
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local')) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
