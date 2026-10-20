<?php

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

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

it('generates exactly the requested number of records dated inside the period', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create(['timezone' => 'UTC']);
    $account = Account::factory()->create(['last_balance' => 0]);
    Account::factory()->create(['last_balance' => 0]);

    $from = CarbonImmutable::now('UTC')->startOfMonth();
    $to = CarbonImmutable::now('UTC')->endOfMonth()->startOfDay();

    $response = $this->actingAs($actor)->postJson("/api/accounts/{$account->id}/records/generate", [
        'count' => 7,
        'date_from' => $from->toDateString(),
        'date_to' => $to->toDateString(),
    ]);

    $response->assertAccepted();
    expect(Record::query()->where('account_id', $account->id)->count())->toBe(7);
    expect(Record::query()->where('recorded_at', '<', $from->utc())->count())->toBe(0);
    expect(Record::query()->where('recorded_at', '>', $to->endOfDay()->utc())->count())->toBe(0);
});

it('generates records of the chosen type and amount, up to 1000', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create(['timezone' => 'UTC']);
    $account = Account::factory()->create(['last_balance' => 0]);

    $this->actingAs($actor)->postJson("/api/accounts/{$account->id}/records/generate", [
        'count' => 5,
        'type' => 'income',
        'amount' => 25000,
    ])->assertAccepted();

    $records = Record::query()->where('account_id', $account->id)->get();
    expect($records)->toHaveCount(5);
    expect($records->every(fn ($r) => $r->type->value === 'income' && (int) $r->amount === 25000))->toBeTrue();

    $this->actingAs($actor)->postJson("/api/accounts/{$account->id}/records/generate", ['count' => 1001])
        ->assertJsonValidationErrors('count');
});

it('deletes all records of an account in the local environment only', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $actor = User::factory()->create();
    $account = Account::factory()->create(['last_balance' => 0]);
    $other = Account::factory()->create(['last_balance' => 0]);
    Record::factory()->count(3)->create(['account_id' => $account->id]);
    Record::factory()->count(2)->create(['account_id' => $other->id]);

    app()->detectEnvironment(fn () => 'production');
    $this->actingAs($actor)->deleteJson("/api/accounts/{$account->id}/records")->assertNotFound();
    expect(Record::query()->count())->toBe(5);

    app()->detectEnvironment(fn () => 'local');
    $this->actingAs($actor)->deleteJson("/api/accounts/{$account->id}/records")->assertNoContent();
    expect(Record::query()->where('account_id', $account->id)->count())->toBe(0);
    expect(Record::query()->where('account_id', $other->id)->count())->toBe(2);
});

it('rejects generating account records outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();
    $account = Account::factory()->create(['last_balance' => 0]);

    $this->actingAs($actor)->postJson("/api/accounts/{$account->id}/records/generate", ['count' => 3])->assertNotFound();
});
