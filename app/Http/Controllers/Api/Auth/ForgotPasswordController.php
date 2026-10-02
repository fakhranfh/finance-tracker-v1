<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\AuthService;
use App\Traits\ForgotPassword\DocumentsApi;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected AuthService $authService,
    ) {}

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendPasswordResetLink($request->validated('email'));

        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }
}
