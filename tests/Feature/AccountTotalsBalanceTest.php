<?php

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use App\Services\AccountMonthlyTotalsService;
use App\Services\AccountYearlyTotalsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('stores the account balance at the end of each month and year', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);
    $other = Account::factory()->create(['last_balance' => 0]);

    Record::factory()->create(['type' => RecordType::Income, 'account_id' => $account->id, 'amount' => 500, 'recorded_at' => CarbonImmutable::parse('2025-03-10 12:00', 'UTC')]);
    Record::factory()->create(['type' => RecordType::Expense, 'account_id' => $account->id, 'amount' => 120, 'recorded_at' => CarbonImmutable::parse('2025-12-31 23:59', 'UTC')]);
    Record::factory()->create(['type' => RecordType::Transfer, 'account_id' => $account->id, 'to_account_id' => $other->id, 'amount' => 80, 'recorded_at' => CarbonImmutable::parse('2026-02-01 00:00', 'UTC')]);

    $months = DB::table('account_monthly_totals')->where('account_id', $account->id)->orderBy('month')->pluck('balance', 'month');
    expect($months->all())->toEqual([
        '2025-03-01' => 1500,
        '2025-12-01' => 1380,
        '2026-02-01' => 1300,
    ]);

    $years = app(AccountYearlyTotalsService::class)->totals($account->id);
    expect($years[0])->toMatchArray(['net' => 380, 'balance' => 1380])
        ->and($years[1])->toMatchArray(['net' => -80, 'balance' => 1300]);

    expect(DB::table('account_monthly_totals')->where('account_id', $account->id)->orderBy('month')->pluck('net')->map(fn ($v) => (float) $v)->all())
        ->toEqual([500, -120, -80]);
});

it('rebuilds balances from the opening balance', function () {
    $account = Account::factory()->create(['last_balance' => 1000]);
    Record::factory()->create(['type' => RecordType::Income, 'account_id' => $account->id, 'amount' => 200, 'recorded_at' => CarbonImmutable::parse('2025-01-05 12:00', 'UTC')]);

    app(AccountMonthlyTotalsService::class)->refresh([$account->id]);

    expect(DB::table('account_monthly_totals')->where('account_id', $account->id)->value('balance'))->toEqual(1200);
});
