<?php

namespace App\Jobs;

use App\Enums\RecordType;
use App\Models\Account;
use App\Services\AccountSampleRecordService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

        $service->generate($account, $this->count, $this->start, $this->end, $this->type, $this->amount);
    }
}
