<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountSampleRecordService;
use Illuminate\Http\Response;

class AccountRecordDeleteController extends Controller
{
    public function __construct(
        protected AccountSampleRecordService $accountSampleRecordService,
    ) {}

    public function __invoke(Account $account): Response
    {
        $this->accountSampleRecordService->deleteAll($account);

        return response()->noContent();
    }
}
