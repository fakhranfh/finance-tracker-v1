<?php

namespace App\Jobs;

use App\Repositories\AccountBalance\AccountBalanceRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Append a snapshot of the account's final balance as of its latest
 * record, so reads only sum the records newer than the newest snapshot.
 */
class RefreshAccountBalanceSnapshot implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public function __construct(public int $accountId) {}

    public function uniqueId(): string
    {
        return (string) $this->accountId;
    }

    public function handle(AccountBalanceRepositoryInterface $balances): void
    {
        $initialBalance = $balances->initialBalance($this->accountId);
        $lastRecordId = $balances->latestRecordId($this->accountId);

        if ($initialBalance === null || $lastRecordId === null) {
            return;
        }

        $base = $balances->latestSnapshot($this->accountId);
        $baseRecordId = $base['last_record_id'] ?? 0;

        if ($lastRecordId <= $baseRecordId) {
            return;
        }

        $balances->storeSnapshot($this->accountId, $lastRecordId, $balances->balanceAfterRecords(
            $this->accountId, $base['balance'] ?? $initialBalance, $baseRecordId, $lastRecordId,
        ));
    }
}
