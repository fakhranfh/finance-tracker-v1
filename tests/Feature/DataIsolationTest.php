<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Goal;
use App\Models\Record;
use App\Models\User;
use App\Models\Webhook;

/**
 * Every user only ever sees and touches their own data.
 */
beforeEach(function () {
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();

    $this->actingAs($this->bob);
    $this->bobAccount = Account::factory()->create();
    $this->bobCategory = Category::factory()->create();
    $this->bobGoal = Goal::factory()->create();
    $this->bobDebt = Debt::factory()->create(['account_id' => $this->bobAccount->id]);
    $this->bobWebhook = Webhook::factory()->create();
    $this->bobRecord = Record::factory()->create(['account_id' => $this->bobAccount->id, 'category_id' => $this->bobCategory->id]);

    $this->actingAs($this->alice);
    $this->aliceAccount = Account::factory()->create();
    $this->aliceCategory = Category::factory()->create(['type' => $this->bobCategory->type]);
});

it('owns what a user creates', function () {
    $this->postJson('/api/accounts', ['name' => 'Mine', 'balance' => 10])->assertCreated();

    expect(Account::query()->where('name', 'Mine')->value('user_id'))->toBe($this->alice->id);
});

it('lists only the signed-in users rows', function (string $path, string $property) {
    $ids = collect($this->getJson($path)->assertOk()->json('data'))->pluck('id');

    expect($ids->all())->not->toContain($this->{$property}->id);
})->with([
    ['/api/accounts', 'bobAccount'],
    ['/api/categories', 'bobCategory'],
    ['/api/goals', 'bobGoal'],
    ['/api/debts', 'bobDebt'],
    ['/api/webhooks', 'bobWebhook'],
    ['/api/records', 'bobRecord'],
]);

it('hides another users rows by id', function (string $path, string $property) {
    $id = $this->{$property}->id;

    $this->getJson("{$path}/{$id}")->assertNotFound();
    $this->putJson("{$path}/{$id}", ['name' => 'Hijacked'])->assertNotFound();
    $this->deleteJson("{$path}/{$id}")->assertNotFound();
})->with([
    ['/api/accounts', 'bobAccount'],
    ['/api/categories', 'bobCategory'],
    ['/api/goals', 'bobGoal'],
    ['/api/debts', 'bobDebt'],
    ['/api/webhooks', 'bobWebhook'],
    ['/api/records', 'bobRecord'],
]);

it('keeps another users rows when bulk deleting', function () {
    $this->deleteJson('/api/accounts', ['ids' => [$this->bobAccount->id]])->assertStatus(422);
    $this->deleteJson('/api/accounts', ['all' => true])->assertNoContent();

    $this->actingAs($this->bob);
    expect(Account::query()->whereKey($this->bobAccount->id)->exists())->toBeTrue()
        ->and(Record::query()->whereKey($this->bobRecord->id)->exists())->toBeTrue();
});

it('rejects records that point at another users account or category', function () {
    $payload = ['type' => 'expense', 'amount' => 1000, 'recorded_at' => now()->toIso8601String()];

    $this->postJson('/api/records', $payload + ['account_id' => $this->bobAccount->id])
        ->assertStatus(422)->assertJsonValidationErrors('account_id');

    $this->postJson('/api/records', $payload + ['account_id' => $this->aliceAccount->id, 'category_id' => $this->bobCategory->id])
        ->assertStatus(422)->assertJsonValidationErrors('category_id');

    $this->postJson('/api/records', ['type' => 'transfer', 'account_id' => $this->aliceAccount->id, 'to_account_id' => $this->bobAccount->id] + $payload)
        ->assertStatus(422)->assertJsonValidationErrors('to_account_id');
});

it('rejects a debt on another users account', function () {
    $this->postJson('/api/debts', [
        'type' => 'debt', 'name' => 'Loan', 'amount' => 1000, 'account_id' => $this->bobAccount->id,
    ])->assertStatus(422)->assertJsonValidationErrors('account_id');
});

it('rejects a webhook that allows another users account', function () {
    $this->postJson('/api/webhooks', [
        'name' => 'Hook', 'allowed_account_ids' => [$this->bobAccount->id],
    ])->assertStatus(422)->assertJsonValidationErrors('allowed_account_ids.0');
});

it('lets two users use the same category name', function () {
    $this->postJson('/api/categories', [
        'name' => $this->bobCategory->name,
        'type' => $this->bobCategory->type->value,
        'color' => '#112233',
        'icon' => config('category.default_icon'),
    ])->assertCreated();
});

it('does not reach another users data through nested routes', function () {
    $this->getJson("/api/goals/{$this->bobGoal->id}/savings")->assertNotFound();
    $this->getJson("/api/goals/{$this->bobGoal->id}/sub-goals")->assertNotFound();
    $this->getJson("/api/debts/{$this->bobDebt->id}/payments")->assertNotFound();
    $this->getJson("/api/debts/{$this->bobDebt->id}/sub-debts")->assertNotFound();
    $this->getJson("/api/accounts/{$this->bobAccount->id}/summary")->assertNotFound();
    $this->getJson("/api/webhooks/{$this->bobWebhook->id}/logs")->assertNotFound();
});

it('summarizes only the signed-in users records', function () {
    $response = $this->getJson('/api/records/summary')->assertOk();

    expect((int) $response->json('data.record_count'))->toBe(0);
});

it('files webhook records under the webhook owner', function () {
    $this->actingAs($this->bob);
    $hook = Webhook::factory()->create(['allowed_account_ids' => null]);
    auth()->logout();

    signedWebhookCall($this, $hook, [
        'type' => 'expense',
        'account_name' => $this->bobAccount->name,
        'amount' => 500,
        'recorded_at' => now()->toIso8601String(),
    ])->assertCreated();

    expect(Record::query()->where('amount', 500)->value('account_id'))->toBe($this->bobAccount->id);
});

it('does not let a webhook use accounts of another user', function () {
    $this->actingAs($this->bob);
    $hook = Webhook::factory()->create(['allowed_account_ids' => null, 'can_create_accounts' => false]);
    auth()->logout();

    signedWebhookCall($this, $hook, [
        'type' => 'expense',
        'account_name' => $this->aliceAccount->name,
        'amount' => 500,
        'recorded_at' => now()->toIso8601String(),
    ])->assertStatus(422);
});
