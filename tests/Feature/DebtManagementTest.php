<?php

use App\Models\Account;
use App\Models\Debt;
use App\Models\Record;
use App\Models\SubDebt;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('rejects a guest from listing debts', function () {
    $this->getJson('/api/debts')->assertStatus(401);
});

it('lists debts via the api', function () {
    $actor = User::factory()->create();
    Debt::factory()->count(3)->create();

    $response = $this->actingAs($actor)->getJson('/api/debts');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'type', 'name', 'amount', 'paid_amount', 'remaining_amount', 'due_date', 'notes', 'is_paid', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(3);
});

it('searches debts via the api', function () {
    $actor = User::factory()->create();
    Debt::factory()->create(['name' => 'Findable entry']);
    Debt::factory()->create(['name' => 'Other']);

    $response = $this->actingAs($actor)->getJson('/api/debts?search=Findable');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('filters debts by type via the api', function () {
    $actor = User::factory()->create();
    Debt::factory()->count(2)->create(['type' => 'debt']);
    Debt::factory()->count(3)->create(['type' => 'receivable']);

    $response = $this->actingAs($actor)->getJson('/api/debts?type=receivable');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('rejects an unknown debt type', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->getJson('/api/debts?type=other')->assertStatus(422);
});

it('bulk deletes only the filtered type when all is set', function () {
    $actor = User::factory()->create();
    Debt::factory()->count(2)->create(['type' => 'debt']);
    Debt::factory()->count(3)->create(['type' => 'receivable']);

    $this->actingAs($actor)->deleteJson('/api/debts', ['all' => true, 'type' => 'receivable'])->assertNoContent();

    expect(Debt::query()->count())->toBe(2);
});

it('creates a debt via the api', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/debts', [
        'type' => 'receivable',
        'account_id' => Account::factory()->create()->id,
        'name' => 'Sample name',
        'amount' => 150000,
        'due_date' => '2026-01-15',
        'notes' => 'Sample notes',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Sample name');
    $response->assertJsonPath('data.type', 'receivable');
    $this->assertDatabaseHas('debts', ['name' => 'Sample name']);
});

it('rejects creating a debt with missing required fields', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/debts', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['type', 'name', 'amount']);
});

it('ignores a paid amount sent with the debt, since it comes from the payments', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/debts', [
        'type' => 'debt',
        'account_id' => Account::factory()->create()->id,
        'name' => 'Budi Santoso',
        'amount' => 100000,
        'paid_amount' => 100000,
        'is_paid' => true,
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.paid_amount', 0);
    $response->assertJsonPath('data.is_paid', false);
});

it('shows a debt via the api', function () {
    $actor = User::factory()->create();
    $target = Debt::factory()->create();

    $response = $this->actingAs($actor)->getJson("/api/debts/{$target->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $target->id);
});

it('updates a debt via the api', function () {
    $actor = User::factory()->create();
    $target = Debt::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/debts/{$target->id}", [
        'type' => 'debt',
        'account_id' => Account::factory()->create()->id,
        'name' => 'Updated name',
        'amount' => 200000,
        'due_date' => '2026-02-20',
        'notes' => 'Updated notes',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Updated name');
});

it('deletes a debt via the api', function () {
    $actor = User::factory()->create();
    $target = Debt::factory()->create();

    $response = $this->actingAs($actor)->deleteJson("/api/debts/{$target->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('debts', ['id' => $target->id]);
});

it('bulk deletes debts by id via the api', function () {
    $actor = User::factory()->create();
    $targets = Debt::factory()->count(3)->create();

    $response = $this->actingAs($actor)->deleteJson('/api/debts', ['ids' => $targets->take(2)->pluck('id')->all()]);

    $response->assertNoContent();
    expect(Debt::query()->count())->toBe(1);
});

it('bulk deletes every debt when all is set', function () {
    $actor = User::factory()->create();
    Debt::factory()->count(3)->create();

    $this->actingAs($actor)->deleteJson('/api/debts', ['all' => true])->assertNoContent();

    expect(Debt::query()->count())->toBe(0);
});

it('generates sample debts via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/debts/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(Debt::query()->count())->toBe(5);
});

it('generates sample data of the requested type only', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/debts/generate', ['count' => 6, 'type' => 'receivable'])->assertOk();

    expect(Debt::query()->where('type', 'receivable')->count())->toBe(6);
    expect(Debt::query()->where('type', 'debt')->count())->toBe(0);
});

it('rejects generating sample debts outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/debts/generate', ['count' => 5])->assertNotFound();
});

it('renders the debts and receivables pages', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->get('/debts')->assertOk()->assertSee('Manage Debts');
    $this->actingAs($actor)->get('/receivables')->assertOk()->assertSee('Manage Receivables');
    $this->actingAs($actor)->get('/receivables/create')->assertOk();
    $this->actingAs($actor)->get('/receivables/1/edit')->assertOk();
});

it('records the money moving in when a debt is created with create_record', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();

    $this->actingAs($actor)->postJson('/api/debts', [
        'type' => 'debt',
        'account_id' => $account->id,
        'name' => 'Pinjaman',
        'amount' => 150000,
        'create_record' => true,
    ])->assertCreated();

    $this->assertDatabaseHas('records', ['type' => 'income', 'account_id' => $account->id, 'amount' => 150000, 'debt_id' => null]);
});

it('records the money moving out when a receivable is created with create_record', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();

    $this->actingAs($actor)->postJson('/api/debts', [
        'type' => 'receivable',
        'account_id' => $account->id,
        'name' => 'Dipinjam',
        'amount' => 80000,
        'create_record' => true,
    ])->assertCreated();

    $this->assertDatabaseHas('records', ['type' => 'expense', 'account_id' => $account->id, 'amount' => 80000]);
});

it('creates no record when create_record is false', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();

    $this->actingAs($actor)->postJson('/api/debts', [
        'type' => 'debt',
        'account_id' => $account->id,
        'name' => 'Tanpa record',
        'amount' => 50000,
        'create_record' => false,
    ])->assertCreated();

    $this->assertDatabaseCount('records', 0);
});

it('forgives a debt, writing off what is left', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 100000]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/forgive")
        ->assertOk()
        ->assertJsonPath('data.is_forgiven', true)
        ->assertJsonPath('data.is_paid', true)
        ->assertJsonPath('data.remaining_amount', 0);

    expect($debt->fresh()->forgiven_at)->not->toBeNull();
});

it('refuses to forgive a debt that is already forgiven', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create();

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/forgive")->assertOk();
    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/forgive")->assertStatus(422);
});

it('rejects a guest from forgiving a debt', function () {
    $this->postJson('/api/debts/1/forgive')->assertStatus(401);
});

it('forgives the unpaid sub debts together with the debt', function () {
    $actor = User::factory()->create();
    $debt = Debt::factory()->create(['amount' => 300000]);
    $sub = SubDebt::factory()->for($debt)->create(['amount' => 100000]);
    $paid = SubDebt::factory()->for($debt)->create(['amount' => 200000]);
    Record::factory()->create(['type' => 'expense', 'amount' => 200000, 'debt_id' => $debt->id, 'sub_debt_id' => $paid->id]);

    $this->actingAs($actor)->postJson("/api/debts/{$debt->id}/forgive")->assertOk();

    expect($sub->fresh()->forgiven_at)->not->toBeNull()
        ->and($sub->fresh()->remaining_amount)->toBe(0)
        ->and($paid->fresh()->forgiven_at)->toBeNull();
});
