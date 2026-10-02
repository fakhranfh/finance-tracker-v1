<?php

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('rejects a guest from listing accounts', function () {
    $this->getJson('/api/accounts')->assertStatus(401);
});

it('lists accounts via the api', function () {
    $actor = User::factory()->create();
    Account::factory()->count(3)->create();

    $response = $this->actingAs($actor)->getJson('/api/accounts');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'name', 'initial_balance', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(3);
});

it('searches accounts via the api', function () {
    $actor = User::factory()->create();
    Account::factory()->create(['name' => 'Findable entry']);
    Account::factory()->create(['name' => 'Other']);

    $response = $this->actingAs($actor)->getJson('/api/accounts?search=Findable');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a account via the api', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/accounts', [
        'name' => 'Sample name',
        'initial_balance' => 9.99,
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Sample name');
    $this->assertDatabaseHas('accounts', ['name' => 'Sample name']);
});

it('rejects creating a account with missing required fields', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/accounts', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'initial_balance']);
});

it('shows a account via the api', function () {
    $actor = User::factory()->create();
    $target = Account::factory()->create();

    $response = $this->actingAs($actor)->getJson("/api/accounts/{$target->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $target->id);
});

it('updates a account via the api', function () {
    $actor = User::factory()->create();
    $target = Account::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/accounts/{$target->id}", [
        'name' => 'Updated name',
        'initial_balance' => 19.5,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Updated name');
});

it('deletes a account via the api', function () {
    $actor = User::factory()->create();
    $target = Account::factory()->create();

    $response = $this->actingAs($actor)->deleteJson("/api/accounts/{$target->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('accounts', ['id' => $target->id]);
});

it('bulk deletes accounts by id via the api', function () {
    $actor = User::factory()->create();
    $targets = Account::factory()->count(3)->create();

    $response = $this->actingAs($actor)->deleteJson('/api/accounts', ['ids' => $targets->take(2)->pluck('id')->all()]);

    $response->assertNoContent();
    expect(Account::query()->count())->toBe(1);
});

it('bulk deletes every account when all is set', function () {
    $actor = User::factory()->create();
    Account::factory()->count(3)->create();

    $this->actingAs($actor)->deleteJson('/api/accounts', ['all' => true])->assertNoContent();

    expect(Account::query()->count())->toBe(0);
});

it('generates sample accounts via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/accounts/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(Account::query()->count())->toBe(5);
});

it('rejects generating sample accounts outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/accounts/generate', ['count' => 5])->assertNotFound();
});
