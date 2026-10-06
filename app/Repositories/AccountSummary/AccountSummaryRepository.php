<?php

namespace App\Repositories\AccountSummary;

use App\Models\Record;
use App\Repositories\AccountBalance\AccountBalanceRepositoryInterface;
use Carbon\CarbonImmutable;

class AccountSummaryRepository implements AccountSummaryRepositoryInterface
{
    public function __construct(protected AccountBalanceRepositoryInterface $balances) {}

    public function totalsAll(int $accountId): array
    {
        return $this->balances->currentTotals($accountId);
    }

    /**
     * Transfers count as outgoing for the source and incoming for the destination.
     */
    public function totalsInRange(int $accountId, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $row = Record::query()
            ->where(fn ($query) => $query->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->when($start, fn ($query, $start) => $query->where('recorded_at', '>=', $start->utc()))
            ->when($end, fn ($query, $end) => $query->where('recorded_at', '<=', $end->utc()))
            ->selectRaw(
                "coalesce(sum(case when type = 'income' and account_id = ? then amount end), 0) as income,
                 coalesce(sum(case when type = 'expense' and account_id = ? then amount end), 0) as expense,
                 coalesce(sum(case when type = 'transfer' and account_id = ? then amount end), 0) as transfer_out,
                 coalesce(sum(case when type = 'transfer' and to_account_id = ? then amount end), 0) as transfer_in,
                 count(*) as record_count",
                [$accountId, $accountId, $accountId, $accountId],
            )
            ->toBase()
            ->first();

        return [
            'income' => (float) $row->income,
            'expense' => (float) $row->expense,
            'transfer_in' => (float) $row->transfer_in,
            'transfer_out' => (float) $row->transfer_out,
            'record_count' => (int) $row->record_count,
        ];
    }
}
