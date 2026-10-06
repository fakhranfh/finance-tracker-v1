<?php

use App\Models\Account;
use App\Models\User;

it('reorders accounts within the slots they occupy', function () {
    $actor = User::factory()->create();
    $first = Account::factory()->create(['sort_order' => 1]);
    $second = Account::factory()->create(['sort_order' => 2]);
    $third = Account::factory()->create(['sort_order' => 3]);

    $response = $this->actingAs($actor)->putJson('/api/accounts/order', [
        'ids' => [$third->id, $first->id],
    ]);

    $response->assertNoContent();

    $this->actingAs($actor)->getJson('/api/accounts?per_page=15')
        ->assertJsonPath('data.0.id', $third->id)
        ->assertJsonPath('data.1.id', $second->id)
        ->assertJsonPath('data.2.id', $first->id);
});

it('lists accounts in their saved order', function () {
    $actor = User::factory()->create();
    Account::factory()->create(['name' => 'Bravo', 'sort_order' => 2]);
    Account::factory()->create(['name' => 'Alpha', 'sort_order' => 1]);

    $this->actingAs($actor)->getJson('/api/accounts')
        ->assertJsonPath('data.0.name', 'Alpha')
        ->assertJsonPath('data.1.name', 'Bravo');
});

it('gives a new account the last position', function () {
    $actor = User::factory()->create();
    Account::factory()->create(['sort_order' => 5]);

    $this->actingAs($actor)->postJson('/api/accounts', [
        'name' => 'Newest',
        'balance' => 0,
    ])->assertCreated();

    expect(Account::query()->where('name', 'Newest')->value('sort_order'))->toBe(6);
});

it('rejects an order with unknown account ids', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->putJson('/api/accounts/order', [
        'ids' => [999999],
    ])->assertStatus(422)->assertJsonValidationErrors(['ids.0']);
});
