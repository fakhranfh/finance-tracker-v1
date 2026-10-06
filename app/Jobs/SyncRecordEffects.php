<?php

namespace App\Jobs;

use App\Services\AccountBalanceSnapshotService;
use App\Services\AccountLastBalanceService;
use App\Services\AccountMonthlyTotalsService;
use App\Services\AccountYearlyTotalsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Apply the side effects of a record change in the background: last balance,
 * snapshots, monthly and yearly totals. Steps run in this order because the
 * snapshot reads last_balance when no snapshot exists yet.
 */
class SyncRecordEffects implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, int|null>  $accountIds
     * @param  array<int, array{type: mixed, account_id: ?int, to_account_id: ?int, amount: string|float, sign: int}>  $balanceChanges
     */
    public function __construct(
        public array $accountIds,
        public array $balanceChanges = [],
        public ?int $invalidateFromRecordId = null,
        public bool $refreshSnapshots = false,
    ) {}

    public function handle(
        AccountLastBalanceService $lastBalance,
        AccountBalanceSnapshotService $snapshots,
        AccountMonthlyTotalsService $monthlyTotals,
        AccountYearlyTotalsService $yearlyTotals,
    ): void {
        foreach ($this->balanceChanges as $change) {
            $lastBalance->apply(
                $change['type'],
                $change['account_id'],
                $change['to_account_id'],
                $change['amount'],
                $change['sign'],
            );
        }

        if ($this->invalidateFromRecordId !== null) {
            $snapshots->invalidate($this->accountIds, $this->invalidateFromRecordId);
        }

        if ($this->refreshSnapshots) {
            $snapshots->refreshIfNeeded($this->accountIds);
        }

        $monthlyTotals->refresh($this->accountIds);
        $yearlyTotals->refresh($this->accountIds);
    }
}
