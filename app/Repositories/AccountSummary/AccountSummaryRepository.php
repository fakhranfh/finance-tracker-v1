<?php

namespace App\Repositories\AccountSummary;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AccountSummaryRepository implements AccountSummaryRepositoryInterface
{
    private const CHUNK_SIZE = 500;

    private const TOTALS_SELECT = 'account_id, coalesce(sum(income), 0) as income, coalesce(sum(expense), 0) as expense,
        coalesce(sum(transfer_in), 0) as transfer_in, coalesce(sum(transfer_out), 0) as transfer_out,
        coalesce(sum(record_count), 0) as record_count';

    public function totalsAll(int $accountId): array
    {
        return $this->bucketFlows($accountId, null, null);
    }

    public function totalsAllFromSnapshot(int $accountId): ?array
    {
        $snapshot = DB::table('account_balance_snapshots')
            ->where('account_id', $accountId)
            ->orderByDesc('last_record_id')
            ->first();

        if (! $snapshot) {
            return null;
        }

        $tail = $this->recordFlowsAfter($accountId, $snapshot->last_record_id);

        return $this->sum($this->flows($snapshot), $tail);
    }

    public function totalsAllFromTotals(int $accountId): ?array
    {
        foreach (['account_yearly_totals', 'account_monthly_totals'] as $table) {
            $row = DB::table($table)
                ->where('account_id', $accountId)
                ->selectRaw(
                    'count(*) as row_count, coalesce(sum(income), 0) as income, coalesce(sum(expense), 0) as expense,
                     coalesce(sum(transfer_in), 0) as transfer_in, coalesce(sum(transfer_out), 0) as transfer_out,
                     coalesce(sum(record_count), 0) as record_count',
                )
                ->first();

            if ($row->row_count > 0) {
                return $this->flows($row);
            }
        }

        return null;
    }

    public function totalsAllFromSnapshotMany(array $accountIds): array
    {
        $snapshots = [];

        foreach (DB::table('account_balance_snapshots')->whereIn('account_id', $accountIds)->orderBy('last_record_id')->get() as $snapshot) {
            $snapshots[(int) $snapshot->account_id] = $snapshot;
        }

        if ($snapshots === []) {
            return [];
        }

        $tails = array_fill_keys(array_keys($snapshots), ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0, 'record_count' => 0]);
        $ids = array_keys($snapshots);
        $records = DB::table('records')
            ->where(fn ($query) => $query->whereIn('account_id', $ids)->orWhereIn('to_account_id', $ids))
            ->where('id', '>', min(array_map(fn ($snapshot) => (int) $snapshot->last_record_id, $snapshots)))
            ->get(['id', 'type', 'account_id', 'to_account_id', 'amount']);

        foreach ($records as $record) {
            foreach (array_unique([(int) $record->account_id, (int) $record->to_account_id]) as $accountId) {
                if (! isset($snapshots[$accountId]) || $record->id <= $snapshots[$accountId]->last_record_id) {
                    continue;
                }

                $amount = (float) $record->amount;
                $isSource = (int) $record->account_id === $accountId;
                $key = match (true) {
                    $record->type === 'income' && $isSource => 'income',
                    $record->type === 'expense' && $isSource => 'expense',
                    $record->type === 'transfer' && $isSource => 'transfer_out',
                    $record->type === 'transfer' => 'transfer_in',
                    default => null,
                };

                if ($key !== null) {
                    $tails[$accountId][$key] += $amount;
                }
                $tails[$accountId]['record_count']++;
            }
        }

        $flows = [];
        foreach ($snapshots as $accountId => $snapshot) {
            $flows[$accountId] = $this->sum($this->flows($snapshot), $tails[$accountId]);
        }

        return $flows;
    }

    public function totalsAllFromTotalsMany(array $accountIds): array
    {
        $flows = [];

        foreach (['account_yearly_totals', 'account_monthly_totals'] as $table) {
            $missing = array_values(array_diff($accountIds, array_keys($flows)));

            if ($missing === []) {
                break;
            }

            foreach (DB::table($table)->whereIn('account_id', $missing)->groupBy('account_id')->selectRaw('count(*) as row_count, '.self::TOTALS_SELECT)->get() as $row) {
                $flows[(int) $row->account_id] = $this->flows($row);
            }
        }

        return $flows;
    }

    public function totalsAllFromRecords(int $accountId): array
    {
        return $this->recordFlowsAfter($accountId, 0);
    }

    /**
     * Flows of the records with an ID above $afterId.
     */
    private function recordFlowsAfter(int $accountId, int $afterId): array
    {
        $row = DB::table('records')
            ->where(fn ($query) => $query->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->where('id', '>', $afterId)
            ->selectRaw(
                "coalesce(sum(case when type = 'income' and account_id = ? then amount end), 0) as income,
                 coalesce(sum(case when type = 'expense' and account_id = ? then amount end), 0) as expense,
                 coalesce(sum(case when type = 'transfer' and account_id = ? then amount end), 0) as transfer_out,
                 coalesce(sum(case when type = 'transfer' and to_account_id = ? then amount end), 0) as transfer_in,
                 count(*) as record_count",
                [$accountId, $accountId, $accountId, $accountId],
            )
            ->first();

        return $this->flows($row);
    }

    public function totalsByYear(int $accountId): array
    {
        $years = [];

        $rows = DB::table('account_yearly_totals')
            ->where('account_id', $accountId)
            ->orderBy('year')
            ->get(['year', 'income', 'expense', 'transfer_in', 'transfer_out', 'record_count', 'net', 'balance']);

        foreach ($rows as $row) {
            $years[(int) $row->year] = [...$this->flows($row), 'net' => round((float) $row->net, 2), 'balance' => round((float) $row->balance, 2)];
        }

        return $years;
    }

    /**
     * Sums the monthly totals of each account into its yearly totals.
     */
    public function rebuildYears(array $accountIds): void
    {
        foreach (array_unique($accountIds) as $accountId) {
            $years = [];

            $rows = DB::table('account_monthly_totals')
                ->where('account_id', $accountId)
                ->orderBy('month')
                ->get(['month', 'income', 'expense', 'transfer_in', 'transfer_out', 'record_count', 'net', 'balance']);

            foreach ($rows as $row) {
                $year = (int) substr((string) $row->month, 0, 4);
                $years[$year] ??= ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0, 'record_count' => 0, 'balance' => 0.0];
                $years[$year] = [...$this->sum($years[$year], $this->flows($row)), 'balance' => round((float) $row->balance, 2)];
            }

            foreach ($years as &$totals) {
                $totals['net'] = $this->net($totals);
            }
            unset($totals);

            DB::transaction(function () use ($accountId, $years): void {
                DB::table('account_yearly_totals')->where('account_id', $accountId)->delete();

                $rows = [];
                foreach ($years as $year => $totals) {
                    $rows[] = [
                        'account_id' => $accountId,
                        'year' => $year,
                        'income' => $totals['income'],
                        'expense' => $totals['expense'],
                        'transfer_in' => $totals['transfer_in'],
                        'transfer_out' => $totals['transfer_out'],
                        'record_count' => $totals['record_count'],
                        'net' => $totals['net'],
                        'balance' => $totals['balance'],
                    ];
                }

                if ($rows !== []) {
                    DB::table('account_yearly_totals')->insert($rows);
                }
            });
        }
    }

    public function totalsInRange(int $accountId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $this->totalsInRangeMany([$accountId], $start, $end)[$accountId];
    }

    public function totalsInRangeMany(array $accountIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $accountIds = array_values(array_unique($accountIds));

        // Whole months are those of the totals timezone, so the range is cut along its month borders.
        $timezone = $this->totalsTimezone();
        $start = $start->setTimezone($timezone);
        $end = $end->setTimezone($timezone);

        // Whole months are [$first, $next): inside the range, and ending at or before it.
        $first = $start->equalTo($start->startOfMonth()) ? $start : $start->startOfMonth()->addMonthNoOverflow();
        $next = $end->addMicrosecond()->startOfMonth();

        if ($first->greaterThanOrEqualTo($next)) {
            return $this->recordFlowsMany($accountIds, $start, $end, true);
        }

        // Whole years are [$yearFrom, $yearTo): inside the whole months, so they come from the yearly totals.
        $yearFrom = $first->month === 1 ? $first : $first->startOfYear()->addYear();
        $yearTo = $next->startOfYear();
        $lastMonth = $next->subMonthNoOverflow();

        if ($yearFrom->lessThan($yearTo)) {
            $parts = [$this->yearFlowsMany($accountIds, $yearFrom->year, $yearTo->year - 1)];

            if ($first->lessThan($yearFrom)) {
                $parts[] = $this->bucketFlowsMany($accountIds, $first->toDateString(), $yearFrom->subMonthNoOverflow()->toDateString());
            }

            if ($yearTo->lessThanOrEqualTo($lastMonth)) {
                $parts[] = $this->bucketFlowsMany($accountIds, $yearTo->toDateString(), $lastMonth->toDateString());
            }

            $months = $this->sumMany($accountIds, ...$parts);
        } else {
            $months = $this->bucketFlowsMany($accountIds, $first->toDateString(), $lastMonth->toDateString());
        }

        return $this->sumMany(
            $accountIds,
            $this->recordFlowsMany($accountIds, $start, $first, false),
            $months,
            $this->recordFlowsMany($accountIds, $next, $end, true),
        );
    }

    /**
     * Transfers count as outgoing for the source and incoming for the destination.
     */
    public function rebuild(array $accountIds, array $openings = []): void
    {
        foreach (array_unique($accountIds) as $accountId) {
            $months = [];
            $timezone = $this->totalsTimezone();

            $records = DB::table('records')
                ->where(fn ($query) => $query->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
                ->select(['type', 'account_id', 'to_account_id', 'amount', 'recorded_at'])
                ->cursor();

            foreach ($records as $record) {
                $month = CarbonImmutable::parse($record->recorded_at, 'UTC')->setTimezone($timezone)->startOfMonth()->toDateString();
                $months[$month] ??= ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0, 'record_count' => 0, 'balance' => 0.0];

                $amount = (float) $record->amount;
                $isSource = (int) $record->account_id === $accountId;
                $isDestination = (int) $record->to_account_id === $accountId;

                match (true) {
                    $record->type === 'income' && $isSource => $months[$month]['income'] += $amount,
                    $record->type === 'expense' && $isSource => $months[$month]['expense'] += $amount,
                    $record->type === 'transfer' && $isSource => $months[$month]['transfer_out'] += $amount,
                    $record->type === 'transfer' && $isDestination => $months[$month]['transfer_in'] += $amount,
                    default => null,
                };
                $months[$month]['record_count']++;
            }

            // Unless the caller knows the opening balance, accounts.last_balance is the
            // balance after every record, so the opening balance is what remains once
            // all net flows are taken back out of it.
            ksort($months);
            $net = 0.0;
            foreach ($months as $totals) {
                $net += $totals['income'] - $totals['expense'] + $totals['transfer_in'] - $totals['transfer_out'];
            }

            $running = round($openings[$accountId] ?? (float) DB::table('accounts')->where('id', $accountId)->value('last_balance') - $net, 2);
            foreach ($months as &$totals) {
                $totals['net'] = $this->net($totals);
                $running = round($running + $totals['net'], 2);
                $totals['balance'] = $running;
            }
            unset($totals);

            DB::transaction(function () use ($accountId, $months): void {
                DB::table('account_monthly_totals')->where('account_id', $accountId)->delete();

                $rows = [];
                foreach ($months as $month => $totals) {
                    $rows[] = [
                        'account_id' => $accountId,
                        'month' => $month,
                        'income' => round($totals['income'], 2),
                        'expense' => round($totals['expense'], 2),
                        'transfer_in' => round($totals['transfer_in'], 2),
                        'transfer_out' => round($totals['transfer_out'], 2),
                        'record_count' => $totals['record_count'],
                        'net' => $totals['net'],
                        'balance' => $totals['balance'],
                    ];
                }

                foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                    DB::table('account_monthly_totals')->insert($chunk);
                }
            });
        }
    }

    public function totalsTimezone(): string
    {
        return (string) (DB::table('users')->orderBy('id')->value('timezone') ?: config('account.totals_timezone', 'UTC'));
    }

    /**
     * Flows of the monthly totals whose month falls within [$fromMonth, $toMonth]. Null bounds are open.
     */
    private function bucketFlows(int $accountId, ?string $fromMonth, ?string $toMonth): array
    {
        return $this->bucketFlowsMany([$accountId], $fromMonth, $toMonth)[$accountId];
    }

    /**
     * @param  array<int, int>  $accountIds
     * @return array<int, array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}>
     */
    private function bucketFlowsMany(array $accountIds, ?string $fromMonth, ?string $toMonth): array
    {
        $rows = DB::table('account_monthly_totals')
            ->whereIn('account_id', $accountIds)
            ->when($fromMonth, fn ($query, $from) => $query->where('month', '>=', $from))
            ->when($toMonth, fn ($query, $to) => $query->where('month', '<=', $to))
            ->groupBy('account_id')
            ->selectRaw(self::TOTALS_SELECT)
            ->get();

        return $this->byAccount($accountIds, $rows);
    }

    /**
     * @param  array<int, int>  $accountIds
     * @return array<int, array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}>
     */
    private function yearFlowsMany(array $accountIds, int $fromYear, int $toYear): array
    {
        $rows = DB::table('account_yearly_totals')
            ->whereIn('account_id', $accountIds)
            ->whereBetween('year', [$fromYear, $toYear])
            ->groupBy('account_id')
            ->selectRaw(self::TOTALS_SELECT)
            ->get();

        return $this->byAccount($accountIds, $rows);
    }

    /**
     * Flows of the records recorded from $from (inclusive) to $to, which is inclusive or exclusive,
     * per account: one query for the source side and one for the destination side of transfers.
     *
     * @param  array<int, int>  $accountIds
     * @return array<int, array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}>
     */
    private function recordFlowsMany(array $accountIds, CarbonImmutable $from, CarbonImmutable $to, bool $toInclusive): array
    {
        $inRange = fn ($query) => $query
            ->where('recorded_at', '>=', $from->utc())
            ->where('recorded_at', $toInclusive ? '<=' : '<', $to->utc());

        $out = DB::table('records')
            ->whereIn('account_id', $accountIds)
            ->tap($inRange)
            ->groupBy('account_id')
            ->selectRaw(
                "account_id,
                 coalesce(sum(case when type = 'income' then amount end), 0) as income,
                 coalesce(sum(case when type = 'expense' then amount end), 0) as expense,
                 coalesce(sum(case when type = 'transfer' then amount end), 0) as transfer_out,
                 0 as transfer_in,
                 count(*) as record_count",
            )
            ->get();

        $in = DB::table('records')
            ->whereIn('to_account_id', $accountIds)
            ->whereColumn('to_account_id', '!=', 'account_id')
            ->tap($inRange)
            ->groupBy('to_account_id')
            ->selectRaw(
                "to_account_id as account_id, 0 as income, 0 as expense, 0 as transfer_out,
                 coalesce(sum(case when type = 'transfer' then amount end), 0) as transfer_in,
                 count(*) as record_count",
            )
            ->get();

        return $this->sumMany($accountIds, $this->byAccount($accountIds, $out), $this->byAccount($accountIds, $in));
    }

    /**
     * @param  array<int, int>  $accountIds
     * @param  iterable<object>  $rows  Rows with an account_id and the flow columns.
     * @return array<int, array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}>
     */
    private function byAccount(array $accountIds, iterable $rows): array
    {
        $empty = ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0, 'record_count' => 0];
        $flows = array_fill_keys($accountIds, $empty);

        foreach ($rows as $row) {
            $flows[(int) $row->account_id] = $this->flows($row);
        }

        return $flows;
    }

    /**
     * @param  array<int, int>  $accountIds
     * @param  array<int, array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}>  ...$parts
     * @return array<int, array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}>
     */
    private function sumMany(array $accountIds, array ...$parts): array
    {
        $sums = [];

        foreach ($accountIds as $accountId) {
            $sums[$accountId] = $this->sum(...array_map(fn (array $part) => $part[$accountId], $parts));
        }

        return $sums;
    }

    /**
     * Money in minus money out, counting transfers in and out of the account.
     *
     * @param  array{income: float, expense: float, transfer_in: float, transfer_out: float}  $flows
     */
    private function net(array $flows): float
    {
        return round($flows['income'] - $flows['expense'] + $flows['transfer_in'] - $flows['transfer_out'], 2);
    }

    private function flows(object $row): array
    {
        return [
            'income' => round((float) $row->income, 2),
            'expense' => round((float) $row->expense, 2),
            'transfer_in' => round((float) $row->transfer_in, 2),
            'transfer_out' => round((float) $row->transfer_out, 2),
            'record_count' => (int) $row->record_count,
        ];
    }

    /**
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}
     */
    private function sum(array ...$parts): array
    {
        $total = ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0, 'record_count' => 0];

        foreach ($parts as $part) {
            foreach ($total as $key => $value) {
                $total[$key] = $key === 'record_count' ? $value + $part[$key] : round($value + $part[$key], 2);
            }
        }

        return $total;
    }
}
