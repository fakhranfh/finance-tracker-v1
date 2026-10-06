<?php

namespace App\Services;

use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;

class AccountYearlyTotalsService
{
    public function __construct(protected AccountSummaryRepositoryInterface $summaries) {}

    /**
     * Recompute the yearly totals of the given accounts, ignoring null IDs.
     * Run after the monthly totals of those accounts are refreshed.
     *
     * @param  array<int, int|null>  $accountIds
     */
    public function refresh(array $accountIds): void
    {
        $ids = array_values(array_unique(array_filter($accountIds)));

        if ($ids !== []) {
            $this->summaries->rebuildYears($ids);
        }
    }

    /**
     * Flow totals of the account for each UTC year, oldest first.
     *
     * @return array<int, array{year: int, income: float, expense: float, transfer_in: float, transfer_out: float, net: float, record_count: int}>
     */
    public function totals(int $accountId): array
    {
        $yearly = [];

        foreach ($this->summaries->totalsByYear($accountId) as $year => $flows) {
            $yearly[] = [
                'year' => $year,
                ...$flows,
                'net' => $flows['income'] - $flows['expense'] + $flows['transfer_in'] - $flows['transfer_out'],
            ];
        }

        return $yearly;
    }
}
