<?php

use App\Models\Account;
use App\Models\Record;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('rejects a guest from listing webhooks', function () {
    $this->getJson('/api/webhooks')->assertStatus(401);
});

it('lists webhooks via the api', function () {
    $actor = User::factory()->create();
    Webhook::factory()->count(3)->create();

    $response = $this->actingAs($actor)->getJson('/api/webhooks');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'name', 'token', 'url', 'is_active', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(3);
});

it('searches webhooks via the api', function () {
    $actor = User::factory()->create();
    Webhook::factory()->create(['name' => 'Findable entry']);
    Webhook::factory()->create(['name' => 'Other']);

    $response = $this->actingAs($actor)->getJson('/api/webhooks?search=Findable');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a webhook via the api', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/webhooks', [
        'name' => 'Sample name',
        'is_active' => true,
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Sample name');
    expect($response->json('data.token'))->toHaveLength(40);
    $this->assertDatabaseHas('webhooks', ['name' => 'Sample name']);
});

it('rejects creating a webhook with missing required fields', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/webhooks', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'is_active']);
});

it('shows a webhook via the api', function () {
    $actor = User::factory()->create();
    $target = Webhook::factory()->create();

    $response = $this->actingAs($actor)->getJson("/api/webhooks/{$target->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $target->id);
});

it('updates a webhook via the api', function () {
    $actor = User::factory()->create();
    $target = Webhook::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/webhooks/{$target->id}", [
        'name' => 'Updated name',
        'is_active' => false,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Updated name');
    $response->assertJsonPath('data.token', $target->token);
});

it('deletes a webhook via the api', function () {
    $actor = User::factory()->create();
    $target = Webhook::factory()->create();

    $response = $this->actingAs($actor)->deleteJson("/api/webhooks/{$target->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('webhooks', ['id' => $target->id]);
});

it('bulk deletes webhooks by id via the api', function () {
    $actor = User::factory()->create();
    $targets = Webhook::factory()->count(3)->create();

    $response = $this->actingAs($actor)->deleteJson('/api/webhooks', ['ids' => $targets->take(2)->pluck('id')->all()]);

    $response->assertNoContent();
    expect(Webhook::query()->count())->toBe(1);
});

it('bulk deletes every webhook when all is set', function () {
    $actor = User::factory()->create();
    Webhook::factory()->count(3)->create();

    $this->actingAs($actor)->deleteJson('/api/webhooks', ['all' => true])->assertNoContent();

    expect(Webhook::query()->count())->toBe(0);
});

it('generates sample webhooks via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/webhooks/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(Webhook::query()->count())->toBe(5);
});

it('rejects generating sample webhooks outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/webhooks/generate', ['count' => 5])->assertNotFound();
});

it('adds a record through an active webhook without authentication', function () {
    $webhook = Webhook::factory()->create(['is_active' => true]);
    $account = Account::factory()->create();

    $response = $this->withHeader('X-Webhook-Token', $webhook->token)->postJson('/api/webhooks/records', [
        'type' => 'expense',
        'account_name' => $account->name,
        'amount' => '12.50',
        'note' => 'Coffee',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('records', ['account_id' => $account->id, 'note' => 'Coffee', 'type' => 'expense']);
});

it('rejects a webhook record with invalid data', function () {
    $webhook = Webhook::factory()->create(['is_active' => true]);

    $this->withHeader('X-Webhook-Token', $webhook->token)->postJson('/api/webhooks/records', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'account_name', 'amount', 'recorded_at']);
});

it('returns 401 for an unknown webhook token', function () {
    $account = Account::factory()->create();

    $this->withHeader('X-Webhook-Token', 'does-not-exist')->postJson('/api/webhooks/records', [
        'type' => 'income',
        'account_name' => $account->name,
        'amount' => '5',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ])->assertUnauthorized();

    expect(Record::query()->count())->toBe(0);
});

it('returns 404 for an inactive webhook', function () {
    $webhook = Webhook::factory()->create(['is_active' => false]);
    $account = Account::factory()->create();

    $this->withHeader('X-Webhook-Token', $webhook->token)->postJson('/api/webhooks/records', [
        'type' => 'income',
        'account_name' => $account->name,
        'amount' => '5',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ])->assertNotFound();

    expect(Record::query()->count())->toBe(0);
});

it('dry-runs a webhook test without saving a record', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create(['is_active' => true]);
    $account = Account::factory()->create();

    $this->actingAs($actor)->postJson("/api/webhooks/{$webhook->id}/test", [
        'type' => 'expense',
        'account_name' => $account->name,
        'amount' => '12.50',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ])->assertOk()->assertJsonPath('data.amount', '12.50');

    expect(Record::query()->count())->toBe(0);
});

it('rejects an invalid webhook test payload', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create();

    $this->actingAs($actor)->postJson("/api/webhooks/{$webhook->id}/test", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'account_name', 'amount', 'recorded_at']);
});

it('reports an inactive webhook as a conflict on test', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create(['is_active' => false]);
    $account = Account::factory()->create();

    $this->actingAs($actor)->postJson("/api/webhooks/{$webhook->id}/test", [
        'type' => 'income',
        'account_name' => $account->name,
        'amount' => '5',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ])->assertStatus(409);
});

it('rejects a guest from testing a webhook', function () {
    $webhook = Webhook::factory()->create();

    $this->postJson("/api/webhooks/{$webhook->id}/test", [])->assertStatus(401);
});

function webhookPayload(Account $account): array
{
    return [
        'type' => 'expense',
        'account_name' => $account->name,
        'amount' => '5',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ];
}

it('saves allowed IPs normalized as a list', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/webhooks', [
        'name' => 'Whitelisted',
        'is_active' => true,
        'allowed_ips' => "203.0.113.5\n198.51.100.0/24, 203.0.113.5",
    ]);

    $response->assertStatus(201)->assertJsonPath('data.allowed_ips', ['203.0.113.5', '198.51.100.0/24']);
});

it('rejects an invalid allowed IP', function () {
    $actor = User::factory()->create();

    foreach (['not an ip', '203.0.113.300', '203.0.113.5/40', 'example.com'] as $value) {
        $this->actingAs($actor)->postJson('/api/webhooks', [
            'name' => 'Bad',
            'is_active' => true,
            'allowed_ips' => $value,
        ])->assertStatus(422)->assertJsonValidationErrors(['allowed_ips']);
    }
});

it('keeps allowed IPs when only the active flag is updated', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create(['allowed_ips' => ['203.0.113.5']]);

    $this->actingAs($actor)->putJson("/api/webhooks/{$webhook->id}", ['name' => $webhook->name, 'is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.allowed_ips', ['203.0.113.5']);
});

it('accepts requests from a whitelisted IP or CIDR range', function () {
    $webhook = Webhook::factory()->create(['allowed_ips' => ['203.0.113.5', '198.51.100.0/24']]);
    $account = Account::factory()->create();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
        ->withHeader('X-Webhook-Token', $webhook->token)->postJson('/api/webhooks/records', webhookPayload($account))->assertStatus(201);
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
        ->withHeader('X-Webhook-Token', $webhook->token)->postJson('/api/webhooks/records', webhookPayload($account))->assertStatus(201);
});

it('rejects requests from other IPs when IPs are whitelisted', function () {
    $webhook = Webhook::factory()->create(['allowed_ips' => ['203.0.113.5', '198.51.100.0/24']]);
    $account = Account::factory()->create();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.6'])
        ->withHeader('X-Webhook-Token', $webhook->token)->postJson('/api/webhooks/records', webhookPayload($account))->assertForbidden();
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.101.1'])
        ->withHeader('X-Webhook-Token', $webhook->token)->postJson('/api/webhooks/records', webhookPayload($account))->assertForbidden();

    expect(Record::query()->count())->toBe(0);
});

it('checks the IP on the webhook test endpoint', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create(['allowed_ips' => ['203.0.113.5']]);
    $account = Account::factory()->create();

    $this->actingAs($actor)->postJson("/api/webhooks/{$webhook->id}/test", webhookPayload($account) + ['ip' => '192.0.2.1'])->assertForbidden();
    $this->actingAs($actor)->postJson("/api/webhooks/{$webhook->id}/test", webhookPayload($account) + ['ip' => '203.0.113.5'])->assertOk();
});

it('saves allowed accounts and types and returns them', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();

    $this->actingAs($actor)->postJson('/api/webhooks', [
        'name' => 'Restricted',
        'is_active' => true,
        'allowed_account_ids' => [$account->id, $account->id],
        'allowed_types' => ['expense'],
    ])->assertStatus(201)
        ->assertJsonPath('data.allowed_account_ids', [$account->id])
        ->assertJsonPath('data.allowed_types', ['expense']);
});

it('rejects unknown allowed accounts and types', function () {
    $actor = User::factory()->create();

    $this->actingAs($actor)->postJson('/api/webhooks', [
        'name' => 'Bad',
        'is_active' => true,
        'allowed_account_ids' => [999999],
        'allowed_types' => ['gift'],
    ])->assertStatus(422)->assertJsonValidationErrors(['allowed_account_ids.0', 'allowed_types.0']);
});

it('clears the restrictions when empty lists are submitted', function () {
    $actor = User::factory()->create();
    $account = Account::factory()->create();
    $webhook = Webhook::factory()->create(['allowed_account_ids' => [$account->id], 'allowed_types' => ['income']]);

    $this->actingAs($actor)->putJson("/api/webhooks/{$webhook->id}", [
        'name' => $webhook->name,
        'is_active' => true,
        'allowed_account_ids' => [],
        'allowed_types' => [],
    ])->assertOk()->assertJsonPath('data.allowed_account_ids', [])->assertJsonPath('data.allowed_types', []);
});

it('only accepts the allowed record types', function () {
    $webhook = Webhook::factory()->create(['allowed_types' => ['income']]);
    $account = Account::factory()->create();

    $this->withHeader('X-Webhook-Token', $webhook->token)
        ->postJson('/api/webhooks/records', webhookPayload($account))->assertForbidden();
    $this->withHeader('X-Webhook-Token', $webhook->token)
        ->postJson('/api/webhooks/records', ['type' => 'income'] + webhookPayload($account))->assertStatus(201);
});

it('only accepts the allowed accounts, including the destination of a transfer', function () {
    $allowed = Account::factory()->create();
    $other = Account::factory()->create();
    $webhook = Webhook::factory()->create(['allowed_account_ids' => [$allowed->id]]);

    $this->withHeader('X-Webhook-Token', $webhook->token)
        ->postJson('/api/webhooks/records', webhookPayload($other))->assertForbidden();
    $this->withHeader('X-Webhook-Token', $webhook->token)
        ->postJson('/api/webhooks/records', ['type' => 'transfer', 'to_account_id' => $other->id] + webhookPayload($allowed))->assertForbidden();
    $this->withHeader('X-Webhook-Token', $webhook->token)
        ->postJson('/api/webhooks/records', webhookPayload($allowed))->assertStatus(201);
});

it('applies the type and account restrictions on the test endpoint', function () {
    $actor = User::factory()->create();
    $allowed = Account::factory()->create();
    $other = Account::factory()->create();
    $webhook = Webhook::factory()->create(['allowed_account_ids' => [$allowed->id], 'allowed_types' => ['expense']]);

    $this->actingAs($actor)->postJson("/api/webhooks/{$webhook->id}/test", webhookPayload($other))->assertForbidden();
    $this->actingAs($actor)->postJson("/api/webhooks/{$webhook->id}/test", ['type' => 'income'] + webhookPayload($allowed))->assertForbidden();
    $this->actingAs($actor)->postJson("/api/webhooks/{$webhook->id}/test", webhookPayload($allowed))->assertOk();
});
