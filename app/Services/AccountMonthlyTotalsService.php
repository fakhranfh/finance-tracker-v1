<?php

namespace App\Services;

use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;

class AccountMonthlyTotalsService
{
    public function __construct(protected AccountSummaryRepositoryInterface $summaries) {}

    /**
     * Recompute the monthly totals of the given accounts, ignoring null IDs.
     *
     * @param  array<int, int|null>  $accountIds
     * @param  array<int, float>  $openings  Opening balance per account ID, when already known.
     */
    public function refresh(array $accountIds, array $openings = []): void
    {
        $ids = array_values(array_unique(array_filter($accountIds)));

        if ($ids !== []) {
            $this->summaries->rebuild($ids, $openings);
        }
    }
}
