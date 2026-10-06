<?php

namespace App\Repositories\AccountSummary;

use Carbon\CarbonImmutable;

interface AccountSummaryRepositoryInterface
{
    /**
     * Flow totals of the account over all its records, read from the latest snapshot.
     *
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}
     */
    public function totalsAll(int $accountId): array;

    /**
     * Flow totals of the account for records recorded within the range. Null bounds are open.
     *
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}
     */
    public function totalsInRange(int $accountId, ?CarbonImmutable $start, ?CarbonImmutable $end): array;
}
