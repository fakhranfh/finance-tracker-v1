<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Record;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AccountSummaryService
{
    public const PRESETS = ['this_month', 'last_month', 'last_30_days', 'this_year', 'all', 'custom'];

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
     * Transfers count as outgoing for the source and incoming for the destination.
     *
     * @param  array{start: ?CarbonImmutable, end: ?CarbonImmutable}  $range
     * @return array{income: float, expense: float, transfer_in: float, transfer_out: float, net: float, record_count: int}
     */
    private function totals(int $accountId, array $range): array
    {
        $row = Record::query()
            ->where(fn ($query) => $query->where('account_id', $accountId)->orWhere('to_account_id', $accountId))
            ->when($range['start'], fn ($query, $start) => $query->where('recorded_at', '>=', $start->utc()))
            ->when($range['end'], fn ($query, $end) => $query->where('recorded_at', '<=', $end->utc()))
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

        $income = (float) $row->income;
        $expense = (float) $row->expense;
        $transferIn = (float) $row->transfer_in;
        $transferOut = (float) $row->transfer_out;

        return [
            'income' => $income,
            'expense' => $expense,
            'transfer_in' => $transferIn,
            'transfer_out' => $transferOut,
            'net' => $income - $expense + $transferIn - $transferOut,
            'record_count' => (int) $row->record_count,
        ];
    }
}
