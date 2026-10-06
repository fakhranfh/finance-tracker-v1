<?php

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use App\Models\User;
use Carbon\CarbonImmutable;

it('totals income, expense and transfers of the account within the range', function () {
    $actor = User::factory()->create(['timezone' => 'UTC']);
    $account = Account::factory()->create(['last_balance' => 0]);
    $other = Account::factory()->create(['last_balance' => 0]);

    Record::factory()->create(['type' => RecordType::Income, 'account_id' => $account->id, 'amount' => 500, 'recorded_at' => now()]);
    Record::factory()->create(['type' => RecordType::Expense, 'account_id' => $account->id, 'amount' => 120, 'recorded_at' => now()]);
    Record::factory()->create(['type' => RecordType::Transfer, 'account_id' => $account->id, 'to_account_id' => $other->id, 'amount' => 80, 'recorded_at' => now()]);
    Record::factory()->create(['type' => RecordType::Transfer, 'account_id' => $other->id, 'to_account_id' => $account->id, 'amount' => 30, 'recorded_at' => now()]);
    Record::factory()->create(['type' => RecordType::Income, 'account_id' => $account->id, 'amount' => 999, 'recorded_at' => now()->subYear()]);

    $response = $this->actingAs($actor)->getJson("/api/accounts/{$account->id}/summary?range=this_month");

    $response->assertOk();
    $response->assertJsonPath('data.range.preset', 'this_month');
    $response->assertJsonPath('data.totals.income', 500);
    $response->assertJsonPath('data.totals.expense', 120);
    $response->assertJsonPath('data.totals.transfer_in', 30);
    $response->assertJsonPath('data.totals.transfer_out', 80);
    $response->assertJsonPath('data.totals.net', 330);
    $response->assertJsonPath('data.totals.record_count', 4);
});

it('uses the custom date range from the query string', function () {
    $actor = User::factory()->create(['timezone' => 'UTC']);
    $account = Account::factory()->create(['last_balance' => 0]);

    Record::factory()->create(['type' => RecordType::Income, 'account_id' => $account->id, 'amount' => 700, 'recorded_at' => CarbonImmutable::parse('2025-03-10 12:00', 'UTC')]);
    Record::factory()->create(['type' => RecordType::Income, 'account_id' => $account->id, 'amount' => 900, 'recorded_at' => CarbonImmutable::parse('2025-06-10 12:00', 'UTC')]);

    $response = $this->actingAs($actor)->getJson("/api/accounts/{$account->id}/summary?range=custom&from=2025-03-01&to=2025-03-31");

    $response->assertOk();
    $response->assertJsonPath('data.range.from', '2025-03-01');
    $response->assertJsonPath('data.totals.income', 700);
    $response->assertJsonPath('data.totals.record_count', 1);
});

it('falls back to this month when the custom range is invalid', function () {
    $actor = User::factory()->create(['timezone' => 'UTC']);
    $account = Account::factory()->create(['last_balance' => 0]);

    $response = $this->actingAs($actor)->getJson("/api/accounts/{$account->id}/summary?range=custom&from=2025-05-10&to=2025-05-01");

    $response->assertOk();
    $response->assertJsonPath('data.range.preset', 'custom');
    $response->assertJsonPath('data.range.from', CarbonImmutable::now('UTC')->startOfMonth()->toDateString());
});
