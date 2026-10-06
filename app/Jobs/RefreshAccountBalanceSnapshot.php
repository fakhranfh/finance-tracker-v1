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
        $lastRecordId = $balances->latestRecordId($this->accountId);

        if ($lastRecordId === null) {
            return;
        }

        $base = $balances->latestSnapshot($this->accountId);
        $baseRecordId = $base['last_record_id'] ?? 0;

        if ($lastRecordId <= $baseRecordId) {
            return;
        }

        // Without a snapshot, last_balance already reflects every record.
        $balance = $base === null
            ? $balances->lastBalance($this->accountId)
            : $balances->balanceAfterRecords($this->accountId, $base['balance'], $baseRecordId, $lastRecordId);

        if ($balance === null) {
            return;
        }

        $flows = $balances->flowsAfterRecords(
            $this->accountId,
            $base['flows'] ?? ['income' => 0, 'expense' => 0, 'transfer_in' => 0, 'transfer_out' => 0, 'net' => 0],
            $baseRecordId,
            $lastRecordId,
        );

        $balances->storeSnapshot($this->accountId, $lastRecordId, $balance, $flows);
    }
}
