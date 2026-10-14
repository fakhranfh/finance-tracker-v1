<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add a nonce-based Content-Security-Policy header to every response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        // Swagger UI (local only) relies on inline scripts and styles.
        if (app()->environment('local') && $request->is('api/documentation*', 'docs*')) {
            return $response;
        }

        $nonce = Vite::cspNonce();
        // The Vite dev server (present only while public/hot exists) cannot be matched by host for IPv6 origins.
        $hot = file_exists(public_path('hot'));
        $dev = $hot ? ' http:' : '';
        $devWs = $hot ? ' ws:' : '';

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$dev}",
            "style-src 'self' 'unsafe-inline'{$dev}",
            "img-src 'self' data: blob:",
            "font-src 'self' data:{$dev}",
            "connect-src 'self'{$dev}{$devWs}",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]));

        return $response;
    }
}
