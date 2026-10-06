<?php

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use App\Services\AccountYearlyTotalsService;
use Carbon\CarbonImmutable;

it('totals the account per calendar year, oldest first', function () {
    $account = Account::factory()->create(['last_balance' => 0]);
    $other = Account::factory()->create(['last_balance' => 0]);

    Record::factory()->create(['type' => RecordType::Income, 'account_id' => $account->id, 'amount' => 500, 'recorded_at' => CarbonImmutable::parse('2025-03-10 12:00', 'UTC')]);
    Record::factory()->create(['type' => RecordType::Expense, 'account_id' => $account->id, 'amount' => 120, 'recorded_at' => CarbonImmutable::parse('2025-12-31 23:59', 'UTC')]);
    Record::factory()->create(['type' => RecordType::Transfer, 'account_id' => $account->id, 'to_account_id' => $other->id, 'amount' => 80, 'recorded_at' => CarbonImmutable::parse('2026-01-01 00:00', 'UTC')]);
    Record::factory()->create(['type' => RecordType::Transfer, 'account_id' => $other->id, 'to_account_id' => $account->id, 'amount' => 30, 'recorded_at' => CarbonImmutable::parse('2026-06-15 08:00', 'UTC')]);

    $totals = app(AccountYearlyTotalsService::class)->totals($account->id);

    expect($totals)->toHaveCount(2)
        ->and($totals[0])->toMatchArray(['year' => 2025, 'income' => 500.0, 'expense' => 120.0, 'net' => 380.0, 'record_count' => 2])
        ->and($totals[1])->toMatchArray(['year' => 2026, 'transfer_in' => 30.0, 'transfer_out' => 80.0, 'net' => -50.0, 'record_count' => 2]);
});

it('keeps the stored yearly totals current when records change or are deleted', function () {
    $account = Account::factory()->create(['last_balance' => 0]);

    $record = Record::factory()->create(['type' => RecordType::Expense, 'account_id' => $account->id, 'amount' => 100, 'recorded_at' => CarbonImmutable::parse('2025-05-01 12:00', 'UTC')]);
    $service = app(AccountYearlyTotalsService::class);

    expect($service->totals($account->id)[0])->toMatchArray(['year' => 2025, 'expense' => 100.0]);

    $record->update(['amount' => 250]);
    expect($service->totals($account->id)[0])->toMatchArray(['year' => 2025, 'expense' => 250.0]);

    $record->delete();
    expect($service->totals($account->id))->toBe([]);
});

it('returns no years for an account without records', function () {
    $account = Account::factory()->create(['last_balance' => 0]);

    expect(app(AccountYearlyTotalsService::class)->totals($account->id))->toBe([]);
});
