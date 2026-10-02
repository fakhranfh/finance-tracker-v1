<?php

namespace App\Services;

use App\Jobs\RefreshAccountBalanceSnapshot;
use App\Repositories\AccountBalance\AccountBalanceRepositoryInterface;

class AccountBalanceSnapshotService
{
    public function __construct(protected AccountBalanceRepositoryInterface $balances) {}

    /**
     * Drop snapshots that cover the given record ID or later (all when null)
     * and append a fresh one in the background.
     *
     * @param  array<int, int|null>  $accountIds
     */
    public function invalidate(array $accountIds, ?int $fromRecordId = null): void
    {
        $this->balances->deleteSnapshots($this->unique($accountIds), $fromRecordId)
            ->each(fn ($id) => RefreshAccountBalanceSnapshot::dispatch($id)->afterCommit());
    }

    /**
     * Append snapshots for accounts with enough records since their last one.
     *
     * @param  array<int, int|null>  $accountIds
     */
    public function refreshIfNeeded(array $accountIds): void
    {
        foreach ($this->unique($accountIds) as $id) {
            if ($this->balances->countRecordsSinceSnapshot($id) >= config('balance.snapshot_threshold')) {
                RefreshAccountBalanceSnapshot::dispatch($id)->afterCommit();
            }
        }
    }

    /**
     * @param  array<int, int|null>  $ids
     * @return array<int, int>
     */
    private function unique(array $ids): array
    {
        return array_values(array_unique(array_filter($ids)));
    }
}
