<?php

use App\Models\Debt;
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
    $response->assertJsonStructure(['data' => [['id', 'name', 'amount', 'paid_amount', 'due_date', 'notes', 'is_paid', 'created_at']]]);
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

it('creates a debt via the api', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/debts', [
        'name' => 'Sample name',
        'amount' => 9.99,
        'paid_amount' => 9.99,
        'due_date' => '2026-01-15',
        'notes' => 'Sample notes',
        'is_paid' => true,
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Sample name');
    $this->assertDatabaseHas('debts', ['name' => 'Sample name']);
});

it('rejects creating a debt with missing required fields', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/debts', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'amount', 'paid_amount', 'is_paid']);
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
        'name' => 'Updated name',
        'amount' => 19.5,
        'paid_amount' => 19.5,
        'due_date' => '2026-02-20',
        'notes' => 'Updated notes',
        'is_paid' => false,
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

it('rejects generating sample debts outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/debts/generate', ['count' => 5])->assertNotFound();
});
