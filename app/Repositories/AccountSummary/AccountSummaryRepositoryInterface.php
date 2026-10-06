<?php

namespace App\Repositories\AccountSummary;

use Carbon\CarbonImmutable;

interface AccountSummaryRepositoryInterface
{
    /**
     * Flow totals of the account over all its records, from its monthly totals.
     *
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}
     */
    public function totalsAll(int $accountId): array;

    /**
     * Flow totals of the account per UTC year, from its yearly totals. Keys are years, oldest first.
     *
     * @return array<int, array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}>
     */
    public function totalsByYear(int $accountId): array;

    /**
     * Recompute the yearly totals of the given accounts from their monthly totals.
     *
     * @param  array<int, int>  $accountIds
     */
    public function rebuildYears(array $accountIds): void;

    /**
     * Flow totals of the account for records recorded between start and end (both inclusive).
     *
     * Whole UTC months inside the range come from the monthly totals; the partial months
     * at either edge are read from the records.
     *
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}
     */
    public function totalsInRange(int $accountId, CarbonImmutable $start, CarbonImmutable $end): array;

    /**
     * Recompute the monthly totals of the given accounts from their records.
     *
     * @param  array<int, int>  $accountIds
     */
    public function rebuild(array $accountIds): void;
}
