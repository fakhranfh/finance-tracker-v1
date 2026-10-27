<?php

use App\Models\Account;
use App\Models\Debt;
use App\Models\Record;
use App\Models\SubDebt;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

function debtPayload(array $overrides = []): array
{
    return [
        'type' => 'debt',
        'account_id' => Account::factory()->create()->id,
        'name' => 'Budi Santoso',
        'amount' => 300000,
        ...$overrides,
    ];
}

it('rejects a guest from listing sub debts', function () {
    $debt = Debt::factory()->create();

    $this->getJson("/api/debts/{$debt->id}/sub-debts")->assertStatus(401);
});

it('lists the sub debts of a debt with a summary', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $first = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    $second = SubDebt::factory()->for($debt)->create(['amount' => 200000]);
    Record::factory()->payingOff($debt, $first)->create(['amount' => 100000]);
    Record::factory()->payingOff($debt, $second)->create(['amount' => 50000]);
    SubDebt::factory()->create();

    $response = $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/sub-debts");

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'debt_id', 'name', 'amount', 'paid_amount', 'remaining_amount', 'due_date', 'is_paid', 'note']], 'summary']);
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('summary'))->toBe(['total' => 2, 'paid' => 1, 'amount_total' => 300000, 'paid_total' => 150000]);
});

it('creates a debt with sub debts and derives its amount from them', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/debts', debtPayload([
        'amount' => 1,
        'sub_debts' => [
            ['name' => 'Cicilan 1', 'amount' => 100000, 'due_date' => '2026-12-01', 'note' => 'Transfer BCA'],
            ['name' => 'Cicilan 2', 'amount' => 200000],
        ],
    ]));

    $response->assertStatus(201);
    $response->assertJsonPath('data.amount', 300000);
    $response->assertJsonPath('data.paid_amount', 0);
    $response->assertJsonPath('data.is_paid', false);
    expect(Debt::query()->findOrFail($response->json('data.id'))->subDebts()->count())->toBe(2);
});

it('replaces the sub debts when updating a debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 300000]);
    $keep = SubDebt::factory()->for($debt)->create(['name' => 'Lama', 'amount' => 100000]);
    $drop = SubDebt::factory()->for($debt)->create(['amount' => 200000]);

    $response = $this->actingAs($actor)->putJson("/api/debts/{$debt->id}", debtPayload([
        'sub_debts' => [
            ['id' => $keep->id, 'name' => 'Diubah', 'amount' => 150000],
            ['name' => 'Baru', 'amount' => 50000],
        ],
    ]));

    $response->assertOk();
    $response->assertJsonPath('data.amount', 200000);
    $this->assertDatabaseHas('sub_debts', ['id' => $keep->id, 'name' => 'Diubah', 'amount' => 150000]);
    $this->assertDatabaseMissing('sub_debts', ['id' => $drop->id]);
    expect($debt->subDebts()->count())->toBe(2);
});

it('removes every sub debt when an empty list is sent and keeps the given amounts', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    SubDebt::factory()->for($debt)->count(2)->create();

    $response = $this->actingAs($actor)->putJson("/api/debts/{$debt->id}", debtPayload(['amount' => 500000, 'sub_debts' => []]));

    $response->assertOk();
    $response->assertJsonPath('data.amount', 500000);
    expect($debt->subDebts()->count())->toBe(0);
});

it('leaves the sub debts alone when the update has no sub_debts key', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    SubDebt::factory()->for($debt)->count(2)->create();

    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}", debtPayload(['amount' => 500000]))->assertOk();

    expect($debt->subDebts()->count())->toBe(2);
});

it('rejects a sub debt without a name or amount', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/debts', debtPayload(['sub_debts' => [['note' => 'x']]]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sub_debts.0.name', 'sub_debts.0.amount']);
});

it('rejects a sub debt id that belongs to another debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $foreign = SubDebt::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/debts/{$debt->id}", debtPayload([
        'sub_debts' => [['id' => $foreign->id, 'name' => 'Salah', 'amount' => 1000]],
    ]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sub_debts.0.id']);
});

it('does not reach a sub debt through another debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $foreign = SubDebt::factory()->create();

    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/sub-debts/{$foreign->id}")->assertNotFound();
    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/sub-debts/{$foreign->id}", ['name' => 'X'])->assertNotFound();
});

it('deletes the sub debts together with their debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $sub = SubDebt::factory()->for($debt)->create();

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}")->assertNoContent();

    $this->assertDatabaseMissing('sub_debts', ['id' => $sub->id]);
});

it('rejects a guest from replacing sub debts', function () {
    $debt = Debt::factory()->create();

    $this->putJson("/api/debts/{$debt->id}/sub-debts", ['sub_debts' => []])->assertStatus(401);
});

it('replaces the sub debts of a debt on their own', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 1000]);
    $keep = SubDebt::factory()->for($debt)->create(['name' => 'Lama', 'amount' => 100000]);
    $drop = SubDebt::factory()->for($debt)->create();

    $response = $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/sub-debts", [
        'sub_debts' => [
            ['id' => $keep->id, 'name' => 'Diubah', 'amount' => 100000],
            ['name' => 'Baru', 'amount' => 50000],
        ],
    ]);

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('summary.amount_total', 150000);
    $this->assertDatabaseHas('debts', ['id' => $debt->id, 'amount' => 150000]);
    $this->assertDatabaseHas('sub_debts', ['id' => $keep->id, 'name' => 'Diubah', 'amount' => 100000]);
    $this->assertDatabaseMissing('sub_debts', ['id' => $drop->id]);
});

it('removes every sub debt when an empty list is replaced and keeps the debt amounts', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 777000]);
    SubDebt::factory()->for($debt)->count(2)->create();

    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/sub-debts", ['sub_debts' => []])->assertOk();

    expect($debt->subDebts()->count())->toBe(0);
    $this->assertDatabaseHas('debts', ['id' => $debt->id, 'amount' => 777000]);
});

it('requires the sub_debts list when replacing and validates its rows', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();

    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/sub-debts", [])->assertStatus(422)->assertJsonValidationErrors(['sub_debts']);
    $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/sub-debts", ['sub_debts' => [['name' => 'X']]])
        ->assertStatus(422)->assertJsonValidationErrors(['sub_debts.0.amount']);
});

it('renders the sub debts edit pages of debts and receivables', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->get('/debts/1/sub-debts/edit')->assertOk()->assertSee('Edit sub debts');
    $this->actingAs($actor)->get('/receivables/1/sub-debts/edit')->assertOk()->assertSee('Edit sub receivables');
});

it('renders the sub debts pages of debts and receivables', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->get('/debts/1/sub-debts')->assertOk()->assertSee('Sub debts');
    $this->actingAs($actor)->get('/receivables/1/sub-debts')->assertOk()->assertSee('Sub receivables');
});

it('creates a single sub debt and updates the debt totals', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 1000]);

    $response = $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/sub-debts", [
        'name' => 'Cicilan 1',
        'amount' => 100000,
        'due_date' => '2026-12-01',
        'note' => 'Transfer BCA',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Cicilan 1');
    $response->assertJsonPath('data.paid_amount', 0);
    $response->assertJsonPath('data.is_paid', false);
    $this->assertDatabaseHas('debts', ['id' => $debt->id, 'amount' => 100000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/sub-debts", ['name' => 'Cicilan 2', 'amount' => 50000])->assertStatus(201);
    $this->assertDatabaseHas('debts', ['id' => $debt->id, 'amount' => 150000]);
});

it('rejects an invalid single sub debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/sub-debts", [])->assertStatus(422)->assertJsonValidationErrors(['name', 'amount']);
});

it('rejects a guest from creating a sub debt', function () {
    $debt = Debt::factory()->create();

    $this->postJson("/api/debts/{$debt->id}/sub-debts", ['name' => 'X', 'amount' => 1000])->assertStatus(401);
});

it('edits every detail of a sub debt and keeps what was paid', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $sub = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    Record::factory()->payingOff($debt, $sub)->create(['amount' => 100000]);

    $response = $this->actingAs($actor)->putJson("/api/debts/{$debt->id}/sub-debts/{$sub->id}", [
        'name' => 'Diubah',
        'amount' => 250000,
        'due_date' => '2027-01-10',
        'note' => 'Catatan baru',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Diubah');
    $response->assertJsonPath('data.amount', 250000);
    $response->assertJsonPath('data.paid_amount', 100000);
    $response->assertJsonPath('data.remaining_amount', 150000);
    $response->assertJsonPath('data.is_paid', false);
    $this->assertDatabaseHas('debts', ['id' => $debt->id, 'amount' => 250000]);
});

it('deletes a sub debt and updates the debt totals', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $keep = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    $drop = SubDebt::factory()->for($debt)->create(['amount' => 200000]);

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/sub-debts/{$drop->id}")->assertNoContent();

    $this->assertDatabaseMissing('sub_debts', ['id' => $drop->id]);
    $this->assertDatabaseHas('debts', ['id' => $debt->id, 'amount' => 100000]);
    $this->assertDatabaseHas('sub_debts', ['id' => $keep->id]);
});

it('does not delete a sub debt through another debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $foreign = SubDebt::factory()->create();

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/sub-debts/{$foreign->id}")->assertNotFound();
    $this->assertDatabaseHas('sub_debts', ['id' => $foreign->id]);
});

it('bulk deletes sub debts by id and refuses ids of other debts', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $targets = SubDebt::factory()->for($debt)->count(3)->create();
    $foreign = SubDebt::factory()->create();

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/sub-debts", ['ids' => $targets->take(2)->pluck('id')->all()])->assertNoContent();

    expect($debt->subDebts()->count())->toBe(1);
    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/sub-debts", ['ids' => [$foreign->id]])->assertStatus(422);
});

it('bulk deletes every matching sub debt of the debt when all is set', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    SubDebt::factory()->for($debt)->count(2)->create(['name' => 'Cicilan motor']);
    SubDebt::factory()->for($debt)->create(['name' => 'Uang muka']);
    $foreign = SubDebt::factory()->create(['name' => 'Cicilan motor']);

    $this->actingAs($actor)->deleteJson("/api/debts/{$debt->id}/sub-debts", ['all' => true, 'search' => 'motor'])->assertNoContent();

    expect($debt->subDebts()->count())->toBe(1);
    $this->assertDatabaseHas('sub_debts', ['id' => $foreign->id]);
});

it('reports how many sub debts can be deleted for the current search', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    SubDebt::factory()->for($debt)->count(2)->create(['name' => 'Cicilan motor']);
    SubDebt::factory()->for($debt)->create(['name' => 'Uang muka']);

    $this->actingAs($actor)->getJson("/api/debts/{$debt->id}/sub-debts?search=motor")->assertOk()->assertJsonPath('deletable_total', 2);
});

it('generates sample sub debts for a debt in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();
    $debt = Debt::factory()->create();

    $response = $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/sub-debts/generate", ['count' => 4]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(4);
    expect($debt->subDebts()->count())->toBe(4);
    $this->assertDatabaseHas('debts', ['id' => $debt->id, 'amount' => (int) $debt->subDebts()->sum('amount')]);
});

it('rejects generating sample sub debts outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();
    $debt = Debt::factory()->create();

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/sub-debts/generate", ['count' => 4])->assertNotFound();
});

it('renders the create and edit pages of a single sub debt', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->get('/debts/1/sub-debts/create')->assertOk()->assertSee('Add sub debt');
    $this->actingAs($actor)->get('/debts/1/sub-debts/2/edit')->assertOk()->assertSee('Edit sub debt');
    $this->actingAs($actor)->get('/receivables/1/sub-debts/create')->assertOk()->assertSee('Add sub receivable');
    $this->actingAs($actor)->get('/receivables/1/sub-debts/2/edit')->assertOk()->assertSee('Edit sub receivable');
});

it('forgives a single sub debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $sub = SubDebt::factory()->for($debt)->create(['amount' => 100000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/sub-debts/{$sub->id}/forgive")
        ->assertOk()
        ->assertJsonPath('data.is_forgiven', true)
        ->assertJsonPath('data.is_paid', true)
        ->assertJsonPath('data.remaining_amount', 0);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/sub-debts/{$sub->id}/forgive")->assertStatus(422);
});

it('does not forgive a sub debt through another debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();
    $foreign = SubDebt::factory()->create();

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/sub-debts/{$foreign->id}/forgive")->assertNotFound();
});
