<?php

use App\Enums\RecordType;
use App\Models\Account;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

function recordPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'expense',
        'account_id' => Account::factory()->create()->id,
        'amount' => 26,
        'note' => 'Lunch',
        'recorded_at' => '2026-10-01T10:30:00+07:00',
    ], $overrides);
}

it('rejects a guest from listing records', function () {
    $this->getJson('/api/records')->assertStatus(401);
});

it('renders the record pages', function () {
    $actor = User::factory()->create();

    foreach (['/records', '/records/create', '/records/1/edit'] as $url) {
        $this->actingAs($actor)->get($url)->assertOk();
    }
});

it('lists records newest first via the api', function () {
    $actor = User::factory()->create();
    $old = Record::factory()->create(['recorded_at' => '2026-01-01 10:00:00']);
    $new = Record::factory()->create(['recorded_at' => '2026-02-01 10:00:00']);

    $response = $this->actingAs($actor)->getJson('/api/records');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'type', 'account', 'to_account', 'amount', 'note', 'recorded_at', 'created_at']]]);
    expect($response->json('data.*.id'))->toBe([$new->id, $old->id]);
});

it('searches records by note or account name', function () {
    $actor = User::factory()->create();
    Record::factory()->create(['note' => 'Findable entry']);
    Record::factory()->for(Account::factory()->create(['name' => 'Findable bank']))->create(['note' => 'x']);
    Record::factory()->create(['note' => 'Other']);

    $response = $this->actingAs($actor)->getJson('/api/records?search=Findable');

    expect($response->json('data'))->toHaveCount(2);
});

it('filters records by type, account and date range via the api', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $other = Account::factory()->create();

    $match = Record::factory()->for($account)->create(['type' => 'expense', 'recorded_at' => '2026-03-15 10:00:00']);
    Record::factory()->for($account)->create(['type' => 'income', 'recorded_at' => '2026-03-15 10:00:00']);
    Record::factory()->for($other)->create(['type' => 'expense', 'recorded_at' => '2026-03-15 10:00:00']);
    Record::factory()->for($account)->create(['type' => 'expense', 'recorded_at' => '2026-05-01 10:00:00']);
    $transfer = Record::factory()->for($other)->create([
        'type' => 'transfer',
        'to_account_id' => $account->id,
        'recorded_at' => '2026-03-20 10:00:00',
    ]);

    $ids = fn (array $query) => collect($this->actingAs($actor)->getJson('/api/records?'.http_build_query($query))->json('data'))->pluck('id')->sort()->values()->all();

    expect($ids(['type' => ['expense'], 'account_ids' => [$account->id], 'date_from' => '2026-03-01', 'date_to' => '2026-03-31']))
        ->toBe([$match->id]);
    expect($ids(['account_ids' => [$account->id], 'date_from' => '2026-03-01', 'date_to' => '2026-03-31']))
        ->toBe(collect([$match->id, $transfer->id, Record::where('type', 'income')->value('id')])->sort()->values()->all());
});

it('filters records by an amount range via the api', function () {
    $actor = User::factory()->create();
    $small = Record::factory()->create(['type' => 'expense', 'amount' => 10]);
    $middle = Record::factory()->create(['type' => 'expense', 'amount' => 50]);
    Record::factory()->create(['type' => 'expense', 'amount' => 500]);

    $ids = fn (array $query) => collect($this->actingAs($actor)->getJson('/api/records?'.http_build_query($query))->json('data'))->pluck('id')->sort()->values()->all();

    expect($ids(['amount_min' => 20, 'amount_max' => 100]))->toBe([$middle->id]);
    expect($ids(['amount_min' => 10]))->toHaveCount(3);
    expect($ids(['amount_max' => 10]))->toBe([$small->id]);
});

it('filters records by any of several accounts', function () {
    $actor = User::factory()->create();
    $first = Account::factory()->create();
    $second = Account::factory()->create();
    $third = Account::factory()->create();

    $a = Record::factory()->for($first)->create();
    $b = Record::factory()->for($second)->create();
    Record::factory()->for($third)->create();

    $response = $this->actingAs($actor)->getJson('/api/records?'.http_build_query(['account_ids' => [$first->id, $second->id]]));

    expect(collect($response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});

it('filters records by any of several types', function () {
    $actor = User::factory()->create();
    $income = Record::factory()->create(['type' => 'income']);
    $expense = Record::factory()->create(['type' => 'expense']);
    Record::factory()->create(['type' => 'transfer', 'to_account_id' => Account::factory()->create()->id]);

    $response = $this->actingAs($actor)->getJson('/api/records?'.http_build_query(['type' => ['income', 'expense']]));

    expect(collect($response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$income->id, $expense->id])->sort()->values()->all());
});

it('bulk deletes every record matching the filters', function () {
    $actor = User::factory()->create();
    $keep = Record::factory()->create(['type' => 'income']);
    Record::factory()->count(2)->create(['type' => 'expense']);

    $this->actingAs($actor)->deleteJson('/api/records', ['all' => true, 'type' => ['expense']])->assertNoContent();

    expect(Record::query()->pluck('id')->all())->toBe([$keep->id]);
});

it('creates an income, expense and transfer', function () {
    $actor = User::factory()->create();
    $to = Account::factory()->create();

    $this->actingAs($actor);
    $this->postJson('/api/records', recordPayload(['type' => 'income']))->assertCreated()->assertJsonPath('data.type', 'income');
    $this->postJson('/api/records', recordPayload(['type' => 'expense']))->assertCreated();
    $this->postJson('/api/records', recordPayload(['type' => 'transfer', 'to_account_id' => $to->id]))
        ->assertCreated()
        ->assertJsonPath('data.to_account_id', $to->id);

    expect(Record::count())->toBe(3);
});

it('rejects creating a record with missing required fields', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/records', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'account_id', 'amount', 'recorded_at']);
});

it('requires a different destination account for transfers', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();

    $this->actingAs($actor)->postJson('/api/records', recordPayload(['type' => 'transfer', 'account_id' => $account->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('to_account_id');

    $this->postJson('/api/records', recordPayload(['type' => 'transfer', 'account_id' => $account->id, 'to_account_id' => $account->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('to_account_id');
});

it('drops the destination account when the type is not transfer', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/records', recordPayload(['to_account_id' => Account::factory()->create()->id]))
        ->assertCreated()
        ->assertJsonPath('data.to_account_id', null);
});

it('rejects invalid types and non-positive amounts', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/records', recordPayload(['type' => 'gift', 'amount' => 0]))
        ->assertUnprocessable()->assertJsonValidationErrors(['type', 'amount']);
});

it('shows, updates and deletes a record', function () {
    $actor = User::factory()->create();
    $target = Record::factory()->create(['type' => RecordType::Expense]);

    $this->actingAs($actor)->getJson("/api/records/{$target->id}")->assertOk()->assertJsonPath('data.id', $target->id);

    $this->putJson("/api/records/{$target->id}", recordPayload(['type' => 'income', 'amount' => 20]))
        ->assertOk()
        ->assertJsonPath('data.type', 'income')
        ->assertJsonPath('data.amount', 20);

    $this->deleteJson("/api/records/{$target->id}")->assertNoContent();
    $this->assertDatabaseMissing('records', ['id' => $target->id]);
});

it('bulk deletes records by id and by all', function () {
    $actor = User::factory()->create();
    $targets = Record::factory()->count(3)->create();

    $this->actingAs($actor)->deleteJson('/api/records', ['ids' => $targets->take(2)->pluck('id')->all()])->assertNoContent();
    expect(Record::query()->count())->toBe(1);

    $this->deleteJson('/api/records', ['all' => true])->assertNoContent();
    expect(Record::query()->count())->toBe(0);
});

it('computes account balances from income, expense and transfers', function () {
    $actor = User::factory()->create();
    $cash = Account::factory()->create(['name' => 'Cash', 'last_balance' => 100]);
    $bank = Account::factory()->create(['name' => 'Bank', 'last_balance' => 0]);

    $this->actingAs($actor);
    $this->postJson('/api/records', recordPayload(['type' => 'income', 'account_id' => $cash->id, 'amount' => 50]))->assertCreated();
    $this->postJson('/api/records', recordPayload(['type' => 'expense', 'account_id' => $cash->id, 'amount' => 20]))->assertCreated();
    $this->postJson('/api/records', recordPayload(['type' => 'transfer', 'account_id' => $cash->id, 'to_account_id' => $bank->id, 'amount' => 70]))->assertCreated();

    $balances = collect($this->getJson('/api/accounts')->json('data'))->pluck('balance', 'name');

    expect($balances['Cash'])->toBe(60)
        ->and($balances['Bank'])->toBe(70);
});

it('deletes records when their account is deleted', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $other = Account::factory()->create();
    Record::factory()->for($account)->create();
    Record::factory()->for($other)->create(['type' => RecordType::Transfer, 'to_account_id' => $account->id]);

    $this->actingAs($actor)->deleteJson("/api/accounts/{$account->id}")->assertNoContent();

    expect(Record::count())->toBe(0);
});

it('generates sample records via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/records/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(Record::query()->count())->toBe(5);
});

it('rejects generating sample records outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/records/generate', ['count' => 5])->assertNotFound();
});
