<?php

use App\Models\Account;
use App\Models\User;

it('creates an account with the chosen color and icon', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/accounts', [
        'name' => 'Sample name',
        'balance' => 1000,
        'color' => '#FF1744',
        'icon' => 'bank',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.color', '#FF1744');
    $response->assertJsonPath('data.icon', 'bank');
});

it('updates an account color and icon without changing its balance', function () {
    $actor = User::factory()->create();
    $target = Account::factory()->create(['last_balance' => 1000, 'color' => '#2563EB', 'icon' => 'wallet']);

    $response = $this->actingAs($actor)->putJson("/api/accounts/{$target->id}", [
        'name' => $target->name,
        'balance' => 1000,
        'color' => '#2E7D32',
        'icon' => 'sprout',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.color', '#2E7D32');
    $response->assertJsonPath('data.icon', 'sprout');
    $response->assertJsonPath('data.balance', '1000.00');
    $this->assertDatabaseCount('records', 0);
});

it('rejects a color that is not a hex code', function () {
    $actor = User::factory()->create();
    $target = Account::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/accounts/{$target->id}", [
        'name' => $target->name,
        'balance' => 1000,
        'color' => 'red',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['color']);
});

it('rejects an icon outside the list', function () {
    $actor = User::factory()->create();
    $target = Account::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/accounts/{$target->id}", [
        'name' => $target->name,
        'balance' => 1000,
        'icon' => 'rocket',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['icon']);
});
