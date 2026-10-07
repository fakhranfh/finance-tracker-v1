<?php

namespace App\Jobs;

use App\Repositories\AccountBalance\AccountBalanceRepositoryInterface;
use App\Services\AccountMonthlyTotalsService;
use App\Services\AccountSummaryService;
use App\Services\AccountYearlyTotalsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Apply the side effects of a record change in the background: monthly and
 * yearly totals, last balance and a new balance snapshot. The opening balance
 * is read before the totals are rebuilt, because it is last_balance minus the
 * net of the totals as they were before this change.
 */
class SyncRecordEffects implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, int|null>  $accountIds
     */
    public function __construct(
        public array $accountIds,
        public ?int $invalidateFromRecordId = null,
        public ?int $createdRecordId = null,
        public bool $backfill = false,
    ) {}

    public function handle(
        AccountBalanceRepositoryInterface $balances,
        AccountSummaryService $summaries,
        AccountMonthlyTotalsService $monthlyTotals,
        AccountYearlyTotalsService $yearlyTotals,
    ): void {
        $ids = array_values(array_unique(array_filter($this->accountIds)));

        $openings = [];
        foreach ($this->backfill ? [] : $ids as $id) {
            $openings[$id] = round((float) $balances->lastBalance($id) - $summaries->allTime($id, false)['net'], 2);
        }

        $monthlyTotals->refresh($ids, $openings);
        $yearlyTotals->refresh($ids);

        if ($this->invalidateFromRecordId !== null) {
            $balances->deleteSnapshots($ids, $this->invalidateFromRecordId);
        }

        foreach ($ids as $id) {
            $totals = $summaries->allTime($id, false);
            // A backfill changes no record, so last_balance already includes all of them.
            $opening = $openings[$id] ??= round((float) $balances->lastBalance($id) - $totals['net'], 2);
            $balance = number_format($opening + $totals['net'], 2, '.', '');

            $balances->setLastBalance($id, $balance);

            if ($this->createdRecordId !== null) {
                // One snapshot per new record, even when several arrive before this job runs.
                $base = $balances->latestSnapshot($id, $this->createdRecordId);
                $flows = $balances->flowsAfterRecords(
                    $id,
                    $base['flows'] ?? ['income' => 0, 'expense' => 0, 'transfer_in' => 0, 'transfer_out' => 0, 'net' => 0, 'record_count' => 0],
                    $base['last_record_id'] ?? 0,
                    $this->createdRecordId,
                );
                $balances->storeSnapshot($id, $this->createdRecordId, number_format($opening + (float) $flows['net'], 2, '.', ''), $flows);

                continue;
            }

            $lastRecordId = $balances->latestRecordId($id);

            if ($lastRecordId !== null) {
                $balances->storeSnapshot($id, $lastRecordId, $balance, $totals);
            }
        }
    }
}
