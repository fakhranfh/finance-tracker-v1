<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\Register\DocumentsApi;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected AuthService $authService,
    ) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());
        $token = $this->authService->issueToken($user);

        return (new UserResource($user))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }
}
