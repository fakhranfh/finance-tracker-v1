<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountSummaryController extends Controller
{
    public function __construct(
        protected AccountSummaryService $accountSummaryService,
    ) {}

    public function show(Request $request, Account $account): JsonResponse
    {
        $timezone = $request->user()?->timezone ?: config('app.timezone', 'UTC');

        return response()->json([
            'data' => $this->accountSummaryService->summarize($account, $request, $timezone),
        ]);
    }
}
