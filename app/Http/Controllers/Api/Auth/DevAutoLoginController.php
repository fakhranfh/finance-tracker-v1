<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Local-only: logs in as the first existing user, creating one if the
 * users table is empty. Route is guarded by EnsureLocalEnvironment.
 */
class DevAutoLoginController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        [$user, $token] = $this->authService->devAutoLogin($request, $request->boolean('admin'));

        return (new UserResource($user))
            ->additional(['token' => $token])
            ->response();
    }
}
