<?php

use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('rejects a guest from listing receivables', function () {
    $this->getJson('/api/receivables')->assertStatus(401);
});

it('lists receivables via the api', function () {
    $actor = User::factory()->create();
    Receivable::factory()->count(3)->create();

    $response = $this->actingAs($actor)->getJson('/api/receivables');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'name', 'amount', 'paid_amount', 'due_date', 'notes', 'is_paid', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(3);
});

it('searches receivables via the api', function () {
    $actor = User::factory()->create();
    Receivable::factory()->create(['name' => 'Findable entry']);
    Receivable::factory()->create(['name' => 'Other']);

    $response = $this->actingAs($actor)->getJson('/api/receivables?search=Findable');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a receivable via the api', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/receivables', [
        'name' => 'Sample name',
        'amount' => 9.99,
        'paid_amount' => 9.99,
        'due_date' => '2026-01-15',
        'notes' => 'Sample notes',
        'is_paid' => true,
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Sample name');
    $this->assertDatabaseHas('receivables', ['name' => 'Sample name']);
});

it('rejects creating a receivable with missing required fields', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/receivables', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'amount', 'paid_amount', 'is_paid']);
});

it('shows a receivable via the api', function () {
    $actor = User::factory()->create();
    $target = Receivable::factory()->create();

    $response = $this->actingAs($actor)->getJson("/api/receivables/{$target->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $target->id);
});

it('updates a receivable via the api', function () {
    $actor = User::factory()->create();
    $target = Receivable::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/receivables/{$target->id}", [
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

it('deletes a receivable via the api', function () {
    $actor = User::factory()->create();
    $target = Receivable::factory()->create();

    $response = $this->actingAs($actor)->deleteJson("/api/receivables/{$target->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('receivables', ['id' => $target->id]);
});

it('bulk deletes receivables by id via the api', function () {
    $actor = User::factory()->create();
    $targets = Receivable::factory()->count(3)->create();

    $response = $this->actingAs($actor)->deleteJson('/api/receivables', ['ids' => $targets->take(2)->pluck('id')->all()]);

    $response->assertNoContent();
    expect(Receivable::query()->count())->toBe(1);
});

it('bulk deletes every receivable when all is set', function () {
    $actor = User::factory()->create();
    Receivable::factory()->count(3)->create();

    $this->actingAs($actor)->deleteJson('/api/receivables', ['all' => true])->assertNoContent();

    expect(Receivable::query()->count())->toBe(0);
});

it('generates sample receivables via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/receivables/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(Receivable::query()->count())->toBe(5);
});

it('rejects generating sample receivables outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/receivables/generate', ['count' => 5])->assertNotFound();
});
