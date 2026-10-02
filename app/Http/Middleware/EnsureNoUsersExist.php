<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNoUsersExist
{
    public function handle(Request $request, Closure $next): Response
    {
        if (User::exists()) {
            abort_if($request->expectsJson(), 403, 'Registration is closed.');

            return redirect()->route('login');
        }

        return $next($request);
    }
}
