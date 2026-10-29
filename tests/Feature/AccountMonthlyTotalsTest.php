<?php

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use App\Models\User;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;
use App\Services\AccountManagementService;
use App\Services\RecordManagementService;
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

    $flows = ['income' => 0, 'expense' => 0, 'transfer_in' => 0, 'transfer_out' => 0, 'record_count' => $records->count()];

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

it('matches a brute-force sum for ranges that span whole calendar years', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $other = Account::factory()->create(['last_balance' => 0]);

    foreach (['2023-12-31 23:59:59', '2024-01-01 00:00', '2024-06-15 12:00', '2024-12-31 23:59:59', '2025-01-01 00:00', '2025-07-04 08:00', '2025-12-31 23:00', '2026-01-01 00:00', '2026-03-10 09:00', '2026-03-31 23:59:59'] as $i => $at) {
        Record::factory()->create([
            'account_id' => $account->id, 'to_account_id' => $i % 2 ? $other->id : null,
            'type' => $i % 2 ? RecordType::Transfer : RecordType::Expense,
            'amount' => 10 + $i, 'recorded_at' => CarbonImmutable::parse($at, 'UTC'),
        ]);
    }
    Record::factory()->create(['account_id' => $other->id, 'to_account_id' => $account->id, 'type' => RecordType::Transfer, 'amount' => 7, 'recorded_at' => '2025-03-20 08:00:00']);

    $repo = app(AccountSummaryRepositoryInterface::class);
    $ranges = [
        ['2024-01-01', '2025-12-31'],
        ['2024-01-01', '2024-12-31'],
        ['2023-12-31', '2026-01-01'],
        ['2024-03-15', '2026-03-10'],
        ['2023-06-01', '2026-03-31'],
        ['2024-02-01', '2025-12-31'],
        ['2024-01-01', '2025-11-30'],
    ];

    foreach ($ranges as [$from, $to]) {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $from, 'UTC');
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $to, 'UTC')->endOfDay();

        expect($repo->totalsInRange($account->id, $start, $end))
            ->toEqual(bruteForceFlows($account, $start, $end));
    }
});

it('keeps yearly totals in step after bulk record deletes', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $records = collect(['2024-03-05 10:00', '2024-09-05 10:00', '2025-02-05 10:00'])->map(fn ($at) => Record::factory()->create([
        'account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100, 'recorded_at' => $at,
    ]));

    $start = CarbonImmutable::parse('2024-01-01', 'UTC');
    $end = CarbonImmutable::parse('2025-12-31', 'UTC')->endOfDay();
    $repo = app(AccountSummaryRepositoryInterface::class);

    app(RecordManagementService::class)->deleteMany([$records[0]->id]);
    expect($repo->totalsInRange($account->id, $start, $end))->toEqual(bruteForceFlows($account, $start, $end));

    app(RecordManagementService::class)->deleteMatching();
    expect($repo->totalsInRange($account->id, $start, $end))->toMatchArray(['income' => 0, 'record_count' => 0]);
});

it('keeps the counterpart yearly totals in step when an account is deleted', function () {
    $from = Account::factory()->create(['last_balance' => 0]);
    $to = Account::factory()->create(['last_balance' => 0]);
    foreach (['2024-03-05 10:00', '2025-02-05 10:00'] as $at) {
        Record::factory()->create(['account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 90, 'recorded_at' => $at]);
    }

    $start = CarbonImmutable::parse('2024-01-01', 'UTC');
    $end = CarbonImmutable::parse('2025-12-31', 'UTC')->endOfDay();
    $repo = app(AccountSummaryRepositoryInterface::class);

    expect($repo->totalsInRange($to->id, $start, $end))->toMatchArray(['transfer_in' => 180]);

    app(AccountManagementService::class)->delete($from);

    expect($repo->totalsInRange($to->id, $start, $end))
        ->toEqual(bruteForceFlows($to, $start, $end))
        ->toMatchArray(['transfer_in' => 0, 'record_count' => 0]);
});

it('refreshes the balance in the yearly totals when the balance is set directly', function () {
    $account = Account::factory()->create(['name' => 'Tunai', 'last_balance' => 0]);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100, 'recorded_at' => '2025-02-05 10:00:00']);

    app(AccountManagementService::class)->update($account, ['name' => 'Tunai', 'balance' => 500, 'adjust_by' => 'balance']);

    expect(app(AccountSummaryRepositoryInterface::class)->totalsByYear($account->id)[2025]['balance'])->toBe(500);
});

it('sums the monthly totals for the all-time range', function () {
    $this->actingAs(User::factory()->create());
    $account = app(AccountRepositoryInterface::class)->create(['name' => 'Tunai', 'last_balance' => 0]);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 300, 'recorded_at' => '2025-06-10 12:00:00']);
    Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Expense, 'amount' => 40, 'recorded_at' => '2026-02-10 12:00:00']);

    expect(app(AccountSummaryRepositoryInterface::class)->totalsAll($account->id))
        ->toMatchArray(['income' => 300, 'expense' => 40, 'record_count' => 2]);
});

it('keeps monthly totals in step with edits and deletes', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $record = Record::factory()->create(['account_id' => $account->id, 'type' => RecordType::Income, 'amount' => 100, 'recorded_at' => '2026-03-05 10:00:00']);
    $repo = app(AccountSummaryRepositoryInterface::class);

    $record->update(['amount' => 250, 'recorded_at' => '2026-04-05 10:00:00']);
    expect($repo->totalsAll($account->id))->toMatchArray(['income' => 250, 'record_count' => 1]);

    $record->delete();
    expect($repo->totalsAll($account->id))->toMatchArray(['income' => 0, 'record_count' => 0]);
});

it('refreshes the other side of a transfer when an account is deleted', function () {
    $from = Account::factory()->create(['last_balance' => 0]);
    $to = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->create(['account_id' => $from->id, 'to_account_id' => $to->id, 'type' => RecordType::Transfer, 'amount' => 90, 'recorded_at' => '2026-03-05 10:00:00']);

    expect(app(AccountSummaryRepositoryInterface::class)->totalsAll($to->id))->toMatchArray(['transfer_in' => 90]);

    app(AccountManagementService::class)->delete($from);

    expect(app(AccountSummaryRepositoryInterface::class)->totalsAll($to->id))->toMatchArray(['transfer_in' => 0, 'record_count' => 0]);
});

it("buckets the monthly totals in the first user's timezone", function () {
    User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $account = Account::factory()->create(['last_balance' => 0]);

    // 18:00 UTC on 28 Feb is already 1 March in Jakarta; 16:59 UTC on 31 Mar is still 31 March there.
    foreach (['2026-02-28 16:59:00' => 1, '2026-02-28 18:00:00' => 2, '2026-03-31 16:59:00' => 4, '2026-03-31 17:00:00' => 8] as $at => $amount) {
        Record::factory()->create(['account_id' => $account->id, 'to_account_id' => null, 'type' => RecordType::Income, 'amount' => $amount, 'recorded_at' => $at]);
    }

    $months = DB::table('account_monthly_totals')->where('account_id', $account->id)->orderBy('month')->pluck('income', 'month');
    expect($months->map(fn ($v) => (int) $v)->all())->toBe(['2026-02-01' => 1, '2026-03-01' => 6, '2026-04-01' => 8]);

    $start = CarbonImmutable::parse('2026-03-01', 'Asia/Jakarta')->startOfDay();
    $end = $start->endOfMonth();
    $flows = app(AccountSummaryRepositoryInterface::class)->totalsInRange($account->id, $start, $end);

    expect($flows['income'])->toBe(6)->and($flows['record_count'])->toBe(2);
});
