<?php

namespace App\Jobs;

use App\Enums\RecordType;
use App\Models\Account;
use App\Services\AccountSampleRecordService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;

/**
 * Dev-only: creates sample records on an account in the background, so large
 * counts do not block the request.
 */
class GenerateAccountRecords implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public int $accountId,
        public int $count,
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public ?RecordType $type = null,
        public string|int|float|null $amount = null,
    ) {
        // Workers must run with --queue=high,default so this goes ahead of the
        // SyncRecordEffects jobs that its own records queue up on default.
        $this->onQueue('high');
    }

    public function handle(AccountSampleRecordService $service): void
    {
        $account = Account::query()->find($this->accountId);

        if ($account === null) {
            return;
        }

        // Act as the owner so the sample records only meet the owner's accounts and categories. A worker
        // keeps its process between jobs, so the user is dropped again; inside a signed-in request (sync queue) it stays.
        $signedIn = Auth::check();

        if (! $signedIn) {
            Auth::setUser($account->user);
        }

        try {
            $service->generate($account, $this->count, $this->start, $this->end, $this->type, $this->amount);
        } finally {
            if (! $signedIn) {
                Auth::forgetUser();
            }
        }
    }
}
