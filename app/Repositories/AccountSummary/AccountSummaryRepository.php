<?php

namespace App\Repositories\AccountSummary;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AccountSummaryRepository implements AccountSummaryRepositoryInterface
{
    private const CHUNK_SIZE = 500;

    public function totalsAll(int $accountId): array
    {
        return $this->bucketFlows($accountId, null, null);
    }

    public function totalsByYear(int $accountId): array
    {
        $years = [];

        $rows = DB::table('account_yearly_totals')
            ->where('account_id', $accountId)
            ->orderBy('year')
            ->get(['year', 'income', 'expense', 'transfer_in', 'transfer_out', 'record_count']);

        foreach ($rows as $row) {
            $years[(int) $row->year] = $this->flows($row);
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
                ->get(['month', 'income', 'expense', 'transfer_in', 'transfer_out', 'record_count']);

            foreach ($rows as $row) {
                $year = (int) substr((string) $row->month, 0, 4);
                $years[$year] ??= ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0, 'record_count' => 0];
                $years[$year] = $this->sum($years[$year], $this->flows($row));
            }

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
        $start = $start->utc();
        $end = $end->utc();

        // Whole months are [$first, $next): inside the range, and ending at or before it.
        $first = $start->equalTo($start->startOfMonth()) ? $start : $start->startOfMonth()->addMonthNoOverflow();
        $next = $end->addMicrosecond()->startOfMonth();

        if ($first->greaterThanOrEqualTo($next)) {
            return $this->recordFlows($accountId, $start, $end, true);
        }

        return $this->sum(
            $this->recordFlows($accountId, $start, $first, false),
            $this->bucketFlows($accountId, $first->toDateString(), $next->subMonthNoOverflow()->toDateString()),
            $this->recordFlows($accountId, $next, $end, true),
        );
    }

    /**
     * Transfers count as outgoing for the source and incoming for the destination.
     */
    public function rebuild(array $accountIds): void
    {
        foreach (array_unique($accountIds) as $accountId) {
            $months = [];

            $records = DB::table('records')
                ->where(fn ($query) => $query->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
                ->select(['type', 'account_id', 'to_account_id', 'amount', 'recorded_at'])
                ->cursor();

            foreach ($records as $record) {
                $month = CarbonImmutable::parse($record->recorded_at, 'UTC')->startOfMonth()->toDateString();
                $months[$month] ??= ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0, 'record_count' => 0];

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
                    ];
                }

                foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                    DB::table('account_monthly_totals')->insert($chunk);
                }
            });
        }
    }

    /**
     * Flows of the monthly totals whose month falls within [$fromMonth, $toMonth]. Null bounds are open.
     */
    private function bucketFlows(int $accountId, ?string $fromMonth, ?string $toMonth): array
    {
        $row = DB::table('account_monthly_totals')
            ->where('account_id', $accountId)
            ->when($fromMonth, fn ($query, $from) => $query->where('month', '>=', $from))
            ->when($toMonth, fn ($query, $to) => $query->where('month', '<=', $to))
            ->selectRaw(
                'coalesce(sum(income), 0) as income, coalesce(sum(expense), 0) as expense,
                 coalesce(sum(transfer_in), 0) as transfer_in, coalesce(sum(transfer_out), 0) as transfer_out,
                 coalesce(sum(record_count), 0) as record_count',
            )
            ->first();

        return $this->flows($row);
    }

    /**
     * Flows of the records recorded from $from (inclusive) to $to, which is inclusive or exclusive.
     */
    private function recordFlows(int $accountId, CarbonImmutable $from, CarbonImmutable $to, bool $toInclusive): array
    {
        $row = DB::table('records')
            ->where(fn ($query) => $query->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->where('recorded_at', '>=', $from->utc())
            ->where('recorded_at', $toInclusive ? '<=' : '<', $to->utc())
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
