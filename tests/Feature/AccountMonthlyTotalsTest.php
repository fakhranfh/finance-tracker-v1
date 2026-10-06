<?php

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;
use App\Services\AccountManagementService;
use Carbon\CarbonImmutable;

/**
 * Brute-force flows of the records recorded within [start, end], as the summary should report them.
 */
function bruteForceFlows(Account $account, CarbonImmutable $start, CarbonImmutable $end): array
{
    $records = Record::query()
        ->where(fn ($q) => $q->where('account_id', $account->id)->orWhere('to_account_id', $account->id))
        ->whereBetween('recorded_at', [$start->utc(), $end->utc()])
        ->get();

    $flows = ['income' => 0.0, 'expense' => 0.0, 'transfer_in' => 0.0, 'transfer_out' => 0.0, 'record_count' => $records->count()];

    foreach ($records as $record) {
        $amount = (float) $record->amount;
        match (true) {
            $record->type === RecordType::Income && $record->account_id === $account->id => $flows['income'] += $amount,
            $record->type === RecordType::Expense && $record->account_id === $account->id => $flows['expense'] += $amount,
            $record->type === RecordType::Transfer && $record->account_id === $account->id => $flows['transfer_out'] += $amount,
            $record->type === RecordType::Transfer && $record->to_account_id === $account->id => $flows['transfer_in'] += $amount,
            default => null,
        };
    }

    return $flows;
}

it('matches a brute-force sum for ranges that start and end mid-month', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $other = Account::factory()->create(['last_balance' => 0]);

    foreach (['2026-01-03 10:00', '2026-01-31 23:30', '2026-02-01 00:00', '2026-02-14 12:00', '2026-03-31 23:59:59', '2026-04-01 00:00', '2026-04-20 09:00'] as $i => $at) {
        Record::factory()->create([
            'account_id' => $account->id, 'to_account_id' => $i % 2 ? $other->id : null,
            'type' => $i % 2 ? RecordType::Transfer : RecordType::Income,
            'amount' => 10 + $i, 'recorded_at' => CarbonImmutable::parse($at, 'UTC'),
        ]);
    }
    Record::factory()->create(['account_id' => $other->id, 'to_account_id' => $account->id, 'type' => RecordType::Transfer, 'amount' => 7, 'recorded_at' => '2026-02-20 08:00:00']);

    $repo = app(AccountSummaryRepositoryInterface::class);
    $ranges = [
        ['2026-01-01', '2026-03-31'],
        ['2026-01-03', '2026-02-14'],
        ['2026-01-15', '2026-02-10'],
        ['2026-01-31', '2026-02-01'],
        ['2026-02-01', '2026-02-28'],
        ['2026-01-01', '2026-04-30'],
    ];

    foreach ($ranges as [$from, $to]) {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $from, 'UTC');
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $to, 'UTC')->endOfDay();

        expect($repo->totalsInRange($account->id, $start, $end))
            ->toEqual(bruteForceFlows($account, $start, $end));
    }
});

it('sums the monthly totals for the all-time range', function () {
    $account = app(AccountRepositoryInterface::class)->create(['name' => 'Tunai', 'last_balance' => 0]);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 300, 'recorded_at' => '2025-06-10 12:00:00']);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Expense, 'amount' => 40, 'recorded_at' => '2026-02-10 12:00:00']);

    expect(app(AccountSummaryRepositoryInterface::class)->totalsAll($account->id))
        ->toMatchArray(['income' => 300.0, 'expense' => 40.0, 'record_count' => 2]);
});

it('keeps monthly totals in step with edits and deletes', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $record = Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100, 'recorded_at' => '2026-03-05 10:00:00']);
    $repo = app(AccountSummaryRepositoryInterface::class);

    $record->update(['amount' => 250, 'recorded_at' => '2026-04-05 10:00:00']);
    expect($repo->totalsAll($account->id))->toMatchArray(['income' => 250.0, 'record_count' => 1]);

    $record->delete();
    expect($repo->totalsAll($account->id))->toMatchArray(['income' => 0.0, 'record_count' => 0]);
});

it('refreshes the other side of a transfer when an account is deleted', function () {
    $from = Account::factory()->create(['last_balance' => 0]);
    $to = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->create(['account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 90, 'recorded_at' => '2026-03-05 10:00:00']);

    expect(app(AccountSummaryRepositoryInterface::class)->totalsAll($to->id))->toMatchArray(['transfer_in' => 90.0]);

    app(AccountManagementService::class)->delete($from);

    expect(app(AccountSummaryRepositoryInterface::class)->totalsAll($to->id))->toMatchArray(['transfer_in' => 0.0, 'record_count' => 0]);
});
