<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationNotificationController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('auth.verify_email.already_verified')]);
        }

        $retryAfter = $this->authService->sendVerificationEmail($user);

        if ($retryAfter > 0) {
            return response()->json([
                'message' => __('auth.verify_email.throttled', ['seconds' => $retryAfter]),
                'retry_after' => $retryAfter,
            ], 429);
        }

        return response()->json([
            'message' => __('auth.verify_email.sent'),
            'retry_after' => AuthService::VERIFICATION_COOLDOWN,
        ]);
    }
}
