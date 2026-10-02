<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\Logout\DocumentsApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected AuthService $authService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json([
            'message' => __('auth.logged_out'),
        ]);
    }
}
