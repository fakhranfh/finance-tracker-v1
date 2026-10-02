<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\Login\DocumentsApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected AuthService $authService,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $token = $this->authService->attemptLogin($request->validated(), $request);

        if ($token === null) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 422);
        }

        return (new UserResource(Auth::user()))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(200);
    }
}
