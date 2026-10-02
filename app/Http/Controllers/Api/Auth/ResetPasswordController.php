<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use App\Traits\ResetPassword\DocumentsApi;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected AuthService $authService,
    ) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }
}
