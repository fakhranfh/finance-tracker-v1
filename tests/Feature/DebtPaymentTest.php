<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Record;
use App\Models\SubDebt;
use App\Models\User;

function newPayment(Account $account, array $overrides = []): array
{
    return [
        'account_id' => $account->id,
        'amount' => 40000,
        'note' => 'Cicilan pertama',
        'recorded_at' => '2026-10-20T10:00:00+07:00',
        ...$overrides,
    ];
}

it('rejects a guest from the payments endpoints', function () {
    $debt = Debt::factory()->create();

    $this->getJson("/api/debts/{$debt->id}/payments")->assertStatus(401);
    $this->getJson("/api/debts/{$debt->id}/payments/available")->assertStatus(401);
    $this->postJson("/api/debts/{$debt->id}/payments", [])->assertStatus(401);
    $this->getJson("/api/debts/{$debt->id}/payments/-1")->assertStatus(401);
    $this->putJson("/api/debts/{$debt->id}/payments/-1", [])->assertStatus(401);
});

it('derives what was paid from the linked records', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 100000]);
    Record::factory()->payingOff($debt)->create(['amount' => 30000]);
    Record::factory()->payingOff($debt)->create(['amount' => 20000]);
    Record::factory()->create(['amount' => 99999]);

    $response = $this->actingAs($actor)->getJson("/api/debts/{$debt->id}");

    $response->assertOk();
    $response->assertJsonPath('data.paid_amount', 50000);
    $response->assertJsonPath('data.remaining_amount', 50000);
    $response->assertJsonPath('data.is_paid', false);

    Record::factory()->payingOff($debt)->create(['amount' => 50000]);

    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.is_paid', true)->assertJsonPath('data.remaining_amount', 0);
});

it('shows the paid amount in the debts list and can sort by it', function () {
    $actor = User::factory()->create();
    $small = Debt::factory()->create(['amount' => 100000]);
    $big = Debt::factory()->create(['amount' => 100000]);
    Record::factory()->payingOff($small)->create(['amount' => 10000]);
    Record::factory()->payingOff($big)->create(['amount' => 90000]);

    $response = $this->actingAs($actor)->getJson('/api/debts?sort=paid_amount&direction=desc');

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($big->id);
    expect($response->json('data.0.paid_amount'))->toBe(90000);

    $this->actingAs($actor)->getJson('/api/debts?sort=is_paid&direction=desc')->assertOk();
});

it('lists the payments of a debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 300000]);
    $sub = SubDebt::factory()->for($debt)->create(['amount' => 300000]);
    $other = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    Record::factory()->payingOff($debt, $sub)->create(['amount' => 10000]);
    Record::factory()->payingOff($debt, $other)->create(['amount' => 20000]);
    Record::factory()->payingOff(Debt::factory()->create())->create();

    $all = $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments");
    $all->assertOk();
    $all->assertJsonStructure(['data' => [['id', 'debt_id', 'sub_debt_id', 'sub_debt', 'account', 'category', 'amount', 'note', 'recorded_at']], 'summary']);
    expect($all->json('data'))->toHaveCount(2);
    expect($all->json('summary'))->toBe(['amount' => 300000, 'paid' => 30000, 'remaining' => 270000]);

    $filtered = $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments?sub_debt_id={$sub->id}");
    expect($filtered->json('data'))->toHaveCount(1);
    expect($filtered->json('data.0.sub_debt'))->toBe($sub->name);
});

it('pays a debt off with a new expense record', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 100000]);

    $response = $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['category_id' => $category->id]));

    $response->assertStatus(201);
    $response->assertJsonPath('data.amount', 40000);
    $response->assertJsonPath('data.debt_id', $debt->id);
    $this->assertDatabaseHas('records', ['debt_id' => $debt->id, 'sub_debt_id' => null, 'type' => 'expense', 'account_id' => $account->id, 'category_id' => $category->id, 'amount' => 40000]);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 40000);
});

it('settles a receivable with a new income record', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $debt = Debt::factory()->create(['type' => 'receivable', 'amount' => 100000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account))->assertStatus(201);

    $this->assertDatabaseHas('records', ['debt_id' => $debt->id, 'type' => 'income']);
});

it('rejects a category of the wrong type for the payment', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $incomeCategory = Category::factory()->income()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 100000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['category_id' => $incomeCategory->id]))
        ->assertStatus(422)->assertJsonValidationErrors(['category_id']);
});

it('validates a new payment', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", [])
        ->assertStatus(422)->assertJsonValidationErrors(['account_id', 'amount', 'recorded_at']);
});

it('does not accept a payment above what is still owed', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    Record::factory()->payingOff($debt)->create(['amount' => 70000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['amount' => 40000]))
        ->assertStatus(422)->assertJsonValidationErrors(['amount']);
    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['amount' => 30000]))->assertStatus(201);
    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['amount' => 1]))->assertStatus(422);
});

it('lists only unlinked records of the right type as candidates', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 100000]);
    $usable = Record::factory()->create(['type' => 'expense', 'note' => 'Bayar utang motor']);
    Record::factory()->create(['type' => 'income']);
    Record::factory()->payingOff(Debt::factory()->create())->create();

    $response = $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments/available");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($usable->id);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments/available?search=motor")->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments/available?search=tidakada")->assertOk()->assertJsonCount(0, 'data');
});

it('pays a debt off with an existing record', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 100000]);
    $record = Record::factory()->create(['type' => 'expense', 'amount' => 60000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", ['record_id' => $record->id])->assertStatus(201)->assertJsonPath('data.id', $record->id);

    $this->assertDatabaseHas('records', ['id' => $record->id, 'debt_id' => $debt->id]);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 60000);
});

it('rejects an existing record that is of the wrong type, already linked or too large', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 100000]);
    $income = Record::factory()->create(['type' => 'income']);
    $linked = Record::factory()->payingOff(Debt::factory()->create())->create();
    $large = Record::factory()->create(['type' => 'expense', 'amount' => 150000]);

    foreach ([$income, $linked, $large] as $record) {
        $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", ['record_id' => $record->id])
            ->assertStatus(422)->assertJsonValidationErrors(['record_id']);
    }
});

it('pays a sub debt off and requires one when the debt has sub debts', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 300000]);
    $first = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    $second = SubDebt::factory()->for($debt)->create(['amount' => 200000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account))
        ->assertStatus(422)->assertJsonValidationErrors(['sub_debt_id']);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['sub_debt_id' => $first->id, 'amount' => 100000]))->assertStatus(201);
    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['sub_debt_id' => $first->id, 'amount' => 1000]))
        ->assertStatus(422)->assertJsonValidationErrors(['amount']);

    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/sub-debts")
        ->assertJsonPath('data.0.paid_amount', 100000)
        ->assertJsonPath('data.0.is_paid', true)
        ->assertJsonPath('data.1.paid_amount', 0)
        ->assertJsonPath('summary.paid', 1)
        ->assertJsonPath('summary.paid_total', 100000);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 100000);
    expect($second->refresh()->is_paid)->toBeFalse();
});

it('does not accept a sub debt of another debt', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $foreign = SubDebt::factory()->create();

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['sub_debt_id' => $foreign->id]))
        ->assertStatus(422)->assertJsonValidationErrors(['sub_debt_id']);
});

it('saves a payment without a record when create_record is false', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['type' => 'receivable', 'amount' => 100000]);

    $response = $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", [
        'create_record' => false,
        'amount' => 40000,
        'note' => 'Dibayar tunai',
        'recorded_at' => '2026-10-20T10:00:00+07:00',
    ]);

    $payment = DebtPayment::query()->firstOrFail();
    $response->assertStatus(201)->assertJsonPath('data.id', -$payment->id)->assertJsonPath('data.record_id', null)->assertJsonPath('data.amount', 40000)->assertJsonPath('data.account', null);
    $this->assertDatabaseHas('debt_payments', ['debt_id' => $debt->id, 'sub_debt_id' => null, 'amount' => 40000, 'note' => 'Dibayar tunai']);
    $this->assertDatabaseCount('records', 0);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 40000)->assertJsonPath('data.remaining_amount', 60000);
});

it('also records the payment when create_record is true', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 100000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", newPayment($account, ['create_record' => true]))->assertStatus(201);

    $this->assertDatabaseHas('records', ['debt_id' => $debt->id, 'account_id' => $account->id, 'amount' => 40000]);
    $this->assertDatabaseCount('debt_payments', 0);
});

it('needs no account for a payment without a record but still validates it', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", ['create_record' => false])
        ->assertStatus(422)->assertJsonValidationErrors(['amount', 'recorded_at'])->assertJsonMissingValidationErrors(['account_id']);
    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", ['create_record' => 'maybe'])
        ->assertStatus(422)->assertJsonValidationErrors(['create_record']);
});

it('counts payments without a record in the totals and the limit', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $sub = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    Record::factory()->payingOff($debt, $sub)->create(['amount' => 30000]);
    DebtPayment::query()->create(['debt_id' => $debt->id, 'sub_debt_id' => $sub->id, 'amount' => 50000, 'paid_at' => now()]);

    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 80000);
    $this->actingAs($actor)->getJson('/api/debts?sort=paid_amount&direction=desc')->assertOk()->assertJsonPath('data.0.paid_amount', 80000);
    $this->actingAs($actor)->getJson('/api/debts?sort=is_paid&direction=desc')->assertOk();
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/sub-debts?sort=is_paid")->assertOk()
        ->assertJsonPath('data.0.paid_amount', 80000)->assertJsonPath('summary.paid_total', 80000);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", ['create_record' => false, 'sub_debt_id' => $sub->id, 'amount' => 30000, 'recorded_at' => now()->toIso8601String()])
        ->assertStatus(422)->assertJsonValidationErrors(['amount']);
    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/payments", ['create_record' => false, 'sub_debt_id' => $sub->id, 'amount' => 20000, 'recorded_at' => now()->toIso8601String()])
        ->assertStatus(201);
    expect($sub->refresh()->is_paid)->toBeTrue();
});

it('lists records and payments without a record together', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 300000]);
    $sub = SubDebt::factory()->for($debt)->create(['amount' => 300000]);
    $record = Record::factory()->payingOff($debt)->create(['amount' => 10000, 'note' => 'Lewat rekening', 'recorded_at' => '2026-10-10 10:00:00']);
    $payment = DebtPayment::query()->create(['debt_id' => $debt->id, 'sub_debt_id' => $sub->id, 'amount' => 20000, 'note' => 'Tunai', 'paid_at' => '2026-10-12 10:00:00']);
    DebtPayment::query()->create(['debt_id' => Debt::factory()->create()->id, 'amount' => 5000, 'paid_at' => now()]);

    $all = $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments");

    $all->assertOk();
    $all->assertJsonStructure(['data' => [['id', 'record_id', 'debt_id', 'sub_debt_id', 'sub_debt', 'account', 'category', 'amount', 'note', 'recorded_at']], 'summary']);
    expect($all->json('data.*.id'))->toBe([-$payment->id, $record->id]);
    expect($all->json('data.0.account'))->toBeNull();
    expect($all->json('data.0.sub_debt'))->toBe($sub->name);
    expect($all->json('data.1.record_id'))->toBe($record->id);
    expect($all->json('summary.paid'))->toBe(30000);

    expect($this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments?sub_debt_id={$sub->id}")->json('data'))->toHaveCount(1);
    expect($this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments?search=Tunai")->json('data'))->toHaveCount(1);
    expect($this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments?sort=amount&direction=asc")->json('data.0.amount'))->toBe(10000);
});

it('deletes a payment without a record', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $payment = DebtPayment::query()->create(['debt_id' => $debt->id, 'amount' => 40000, 'paid_at' => now()]);
    $foreign = DebtPayment::query()->create(['debt_id' => Debt::factory()->create()->id, 'amount' => 1000, 'paid_at' => now()]);

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/payments/-{$foreign->id}")->assertNotFound();
    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/payments/-{$payment->id}")->assertNoContent();

    $this->assertDatabaseMissing('debt_payments', ['id' => $payment->id]);
    $this->assertDatabaseHas('debt_payments', ['id' => $foreign->id]);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 0);
});

it('shows a payment without a record', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $payment = DebtPayment::query()->create(['debt_id' => $debt->id, 'amount' => 40000, 'note' => 'Tunai', 'paid_at' => now()]);
    $foreign = DebtPayment::query()->create(['debt_id' => Debt::factory()->create()->id, 'amount' => 1000, 'paid_at' => now()]);
    $record = Record::factory()->payingOff($debt)->create();

    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments/-{$payment->id}")
        ->assertOk()->assertJsonPath('data.id', -$payment->id)->assertJsonPath('data.amount', 40000)->assertJsonPath('data.note', 'Tunai');
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments/-{$foreign->id}")->assertNotFound();
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/payments/{$record->id}")
        ->assertOk()->assertJsonPath('data.id', $record->id)->assertJsonPath('data.account_id', $record->account_id);
    $this->actingAs($actor)->getJson('/api/debts/'.$debt->id.'/payments/'.Record::factory()->create()->id)->assertNotFound();
});

it('edits a record that pays a debt and follows it on the account', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $debt = Debt::factory()->create(['type' => 'debt', 'amount' => 100000]);
    $record = Record::factory()->payingOff($debt)->create(['type' => 'expense', 'amount' => 30000]);

    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/{$record->id}", [
        'account_id' => $account->id,
        'amount' => 60000,
        'note' => 'Diralat',
        'recorded_at' => '2026-10-21T10:00:00+07:00',
    ])->assertOk()->assertJsonPath('data.amount', 60000)->assertJsonPath('data.account_id', $account->id);

    $this->assertDatabaseHas('records', ['id' => $record->id, 'debt_id' => $debt->id, 'type' => 'expense', 'account_id' => $account->id, 'amount' => 60000]);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 60000);
    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/{$record->id}", ['amount' => 100001, 'recorded_at' => now()->toIso8601String()])
        ->assertStatus(422)->assertJsonValidationErrors(['account_id']);
    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/{$record->id}", ['account_id' => $account->id, 'amount' => 100001, 'recorded_at' => now()->toIso8601String()])
        ->assertStatus(422)->assertJsonValidationErrors(['amount']);
});

it('edits a payment without a record', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $sub = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    $other = SubDebt::factory()->for($debt)->create(['amount' => 50000]);
    $payment = DebtPayment::query()->create(['debt_id' => $debt->id, 'sub_debt_id' => $sub->id, 'amount' => 40000, 'paid_at' => now()]);

    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/-{$payment->id}", [
        'sub_debt_id' => $other->id,
        'amount' => 50000,
        'note' => 'Diralat',
        'recorded_at' => '2026-10-21T10:00:00+07:00',
    ])->assertOk()->assertJsonPath('data.amount', 50000)->assertJsonPath('data.sub_debt', $other->name);

    $this->assertDatabaseHas('debt_payments', ['id' => $payment->id, 'sub_debt_id' => $other->id, 'amount' => 50000, 'note' => 'Diralat']);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 50000);
});

it('lets a payment be raised up to what is still owed counting its own amount', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    Record::factory()->payingOff($debt)->create(['amount' => 30000]);
    $payment = DebtPayment::query()->create(['debt_id' => $debt->id, 'amount' => 20000, 'paid_at' => now()]);
    $body = fn (int $amount) => ['amount' => $amount, 'recorded_at' => now()->toIso8601String()];

    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/-{$payment->id}", $body(70001))
        ->assertStatus(422)->assertJsonValidationErrors(['amount']);
    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/-{$payment->id}", $body(70000))->assertOk();
});

it('validates an edited payment and requires a sub debt when the debt has them', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $sub = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    $payment = DebtPayment::query()->create(['debt_id' => $debt->id, 'sub_debt_id' => $sub->id, 'amount' => 10000, 'paid_at' => now()]);

    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/-{$payment->id}", [])
        ->assertStatus(422)->assertJsonValidationErrors(['amount', 'recorded_at']);
    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/-{$payment->id}", ['amount' => 10000, 'recorded_at' => now()->toIso8601String()])
        ->assertStatus(422)->assertJsonValidationErrors(['sub_debt_id']);
    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/-{$payment->id}", ['sub_debt_id' => SubDebt::factory()->create()->id, 'amount' => 10000, 'recorded_at' => now()->toIso8601String()])
        ->assertStatus(422)->assertJsonValidationErrors(['sub_debt_id']);
});

it('does not edit a payment of another debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $foreign = DebtPayment::query()->create(['debt_id' => Debt::factory()->create()->id, 'amount' => 1000, 'paid_at' => now()]);
    $foreignRecord = Record::factory()->payingOff(Debt::factory()->create())->create();
    $body = ['account_id' => Account::factory()->create()->id, 'amount' => 5000, 'recorded_at' => now()->toIso8601String()];

    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/-{$foreign->id}", $body)->assertNotFound();
    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/payments/{$foreignRecord->id}", $body)->assertNotFound();
    $this->assertDatabaseHas('debt_payments', ['id' => $foreign->id, 'amount' => 1000]);
});

it('forgives the sub debts that are not paid off by payments without a record', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 300000]);
    $paid = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    $open = SubDebt::factory()->for($debt)->create(['amount' => 200000]);
    DebtPayment::query()->create(['debt_id' => $debt->id, 'sub_debt_id' => $paid->id, 'amount' => 100000, 'paid_at' => now()]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/forgive")->assertOk();

    expect($paid->refresh()->forgiven_at)->toBeNull();
    expect($open->refresh()->forgiven_at)->not->toBeNull();
});

it('unlinks a payment without deleting its record', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $record = Record::factory()->payingOff($debt)->create(['amount' => 40000]);

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/payments/{$record->id}")->assertNoContent();

    $this->assertDatabaseHas('records', ['id' => $record->id, 'debt_id' => null, 'sub_debt_id' => null]);
    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 0);
});

it('does not unlink a record that pays another debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $record = Record::factory()->payingOff(Debt::factory()->create())->create();

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/payments/{$record->id}")->assertNotFound();
    $this->assertDatabaseHas('records', ['id' => $record->id]);
});

it('keeps the records when their debt is deleted', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $record = Record::factory()->payingOff($debt)->create();

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}")->assertNoContent();

    $this->assertDatabaseHas('records', ['id' => $record->id, 'debt_id' => null]);
});

it('follows a deleted payment record', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);
    $record = Record::factory()->payingOff($debt)->create(['amount' => 40000]);

    $this->actingAs($actor)->deleteJson("/api/records/{$record->id}")->assertNoContent();

    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}")->assertJsonPath('data.paid_amount', 0);
});

it('renders the payments pages of debts and receivables', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->get('/debts/1/payments')->assertOk()->assertSee('Payments');
    $this->actingAs($actor)->get('/debts/1/payments/create')->assertOk()->assertSee('Add payment');
    $this->actingAs($actor)->get('/debts/1/payments/existing')->assertOk()->assertSee('Use an existing record');
    $this->actingAs($actor)->get('/debts/1/payments/2/edit')->assertOk()->assertSee('Edit payment');
    $this->actingAs($actor)->get('/debts/1/payments/-2/edit')->assertOk()->assertSee('Edit payment');
    $this->actingAs($actor)->get('/receivables/1/payments/2/edit')->assertOk()->assertSee('Edit payment received');
    $this->actingAs($actor)->get('/receivables/1/payments')->assertOk()->assertSee('Payments received');
    $this->actingAs($actor)->get('/receivables/1/payments/create')->assertOk()->assertSee('Add payment received');
    $this->actingAs($actor)->get('/receivables/1/payments/existing')->assertOk()->assertSee('Use an existing record');
});
