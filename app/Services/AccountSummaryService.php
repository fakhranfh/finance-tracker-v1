<?php

namespace App\Services;

use App\Jobs\SyncRecordEffects;
use App\Models\Account;
use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AccountSummaryService
{
    public const PRESETS = ['this_month', 'last_month', 'last_30_days', 'this_year', 'all', 'custom'];

    public function __construct(protected AccountSummaryRepositoryInterface $summaries) {}

    /**
     * Summarize income, expense and transfers of one account for the range
     * given in the request (`range`, and `from`/`to` for custom ranges).
     *
     * @return array{range: array{preset: string, from: string, to: string}, totals: array{income: float, expense: float, transfer_in: float, transfer_out: float, net: float, record_count: int}}
     */
    public function summarize(Account $account, Request $request, string $timezone): array
    {
        $range = $this->resolveRange($request, $timezone);
        $totals = $this->totals($account->id, $range);

        return [
            'range' => [
                'preset' => $range['preset'],
                'from' => $range['from'],
                'to' => $range['to'],
            ],
            'totals' => $totals,
        ];
    }

    /**
     * @return array{preset: string, from: string, to: string, start: ?CarbonImmutable, end: ?CarbonImmutable}
     */
    private function resolveRange(Request $request, string $timezone): array
    {
        $now = CarbonImmutable::now($timezone);
        $preset = (string) $request->query('range', 'this_month');

        if (! in_array($preset, self::PRESETS, true)) {
            $preset = 'this_month';
        }

        [$start, $end] = match ($preset) {
            'all' => [null, null],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'last_30_days' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'this_year' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => $this->customRange($request, $timezone) ?? [$now->startOfMonth(), $now->endOfMonth()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };

        return [
            'preset' => $preset,
            'from' => $start?->toDateString() ?? '',
            'to' => $end?->toDateString() ?? '',
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    private function customRange(Request $request, string $timezone): ?array
    {
        $fromInput = (string) $request->query('from');
        $toInput = (string) $request->query('to');

        if (! CarbonImmutable::canBeCreatedFromFormat($fromInput, 'Y-m-d') || ! CarbonImmutable::canBeCreatedFromFormat($toInput, 'Y-m-d')) {
            return null;
        }

        $from = CarbonImmutable::createFromFormat('!Y-m-d', $fromInput, $timezone);
        $to = CarbonImmutable::createFromFormat('!Y-m-d', $toInput, $timezone);

        if ($from->gt($to)) {
            return null;
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * The all-time range reads the balance snapshots first (monthly totals when
     * there is none). Bounded ranges combine whole months and years from the
     * stored totals with the records at either edge: snapshots are keyed by
     * record ID, so they cannot answer date ranges that include backdated records.
     *
     * @param  array{preset: string, start: ?CarbonImmutable, end: ?CarbonImmutable}  $range
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, net: float, record_count: int}
     */
    private function totals(int $accountId, array $range): array
    {
        return $range['preset'] === 'all'
            ? $this->allTime($accountId)
            : $this->withNet($this->summaries->totalsInRange($accountId, $range['start'], $range['end']));
    }

    /**
     * Totals over every record of the account. Reads the newest balance snapshot plus the
     * records after it, then the yearly or monthly totals, then the records themselves.
     * With $useSnapshot false it reads the monthly totals only.
     *
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, net: float, record_count: int}
     */
    public function allTime(int $accountId, bool $useSnapshot = true): array
    {
        if (! $useSnapshot) {
            return $this->withNet($this->summaries->totalsAll($accountId));
        }

        $flows = $this->summaries->totalsAllFromSnapshot($accountId) ?? $this->summaries->totalsAllFromTotals($accountId);

        if ($flows === null) {
            $flows = $this->summaries->totalsAllFromRecords($accountId);

            // Nothing stored yet: have the job fill the snapshot and the totals for next time.
            if ($flows['record_count'] > 0) {
                SyncRecordEffects::request([$accountId], null, null, true);
            }
        }

        return $this->withNet($flows);
    }

    /**
     * allTime for several accounts, with one query per source instead of one per account.
     *
     * @param  array<int, int>  $accountIds
     * @return array<int, array{income: float, expense: float, transfer_in: float, transfer_out: float, net: float, record_count: int}>
     */
    private function allTimeMany(array $accountIds): array
    {
        $flows = $this->summaries->totalsAllFromSnapshotMany($accountIds);
        $flows += $this->summaries->totalsAllFromTotalsMany(array_values(array_diff($accountIds, array_keys($flows))));

        $stale = [];
        foreach (array_diff($accountIds, array_keys($flows)) as $accountId) {
            $flows[$accountId] = $this->summaries->totalsAllFromRecords($accountId);

            if ($flows[$accountId]['record_count'] > 0) {
                $stale[] = $accountId;
            }
        }

        // Nothing stored yet: have the job fill the snapshot and the totals for next time.
        if ($stale !== []) {
            SyncRecordEffects::request($stale, null, null, true);
        }

        return array_map($this->withNet(...), $flows);
    }

    /**
     * Summed flows of several accounts over a date range (all time when $start is null), from
     * the stored totals. A transfer between two of the accounts counts for both, so the
     * record counts are left for the caller.
     *
     * @param  array<int, int>  $accountIds
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, net: float}
     */
    public function flowsOfAccounts(array $accountIds, ?CarbonImmutable $start, ?CarbonImmutable $end): array
    {
        $sum = ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0];

        $accountIds = array_values(array_unique($accountIds));
        $perAccount = $start === null || $end === null
            ? $this->allTimeMany($accountIds)
            : $this->summaries->totalsInRangeMany($accountIds, $start, $end);

        foreach ($perAccount as $flows) {
            foreach ($sum as $key => $value) {
                $sum[$key] = round($value + $flows[$key], 2);
            }
        }

        $net = round($sum['income'] - $sum['expense'] + $sum['transfer_in'] - $sum['transfer_out'], 2);

        return [...$sum, 'net' => $net];
    }

    /**
     * @param  array{income: float, expense: float, transfer_in: float, transfer_out: float, record_count: int}  $flows
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, net: float, record_count: int}
     */
    private function withNet(array $flows): array
    {
        return [
            ...$flows,
            'net' => $flows['income'] - $flows['expense'] + $flows['transfer_in'] - $flows['transfer_out'],
        ];
    }
}
