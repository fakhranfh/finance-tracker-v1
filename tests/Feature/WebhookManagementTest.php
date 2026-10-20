<?php

use App\Models\Account;
use App\Models\Record;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookLog;
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

    $response = signedWebhookCall($this, $webhook, [
        'type' => 'expense',
        'account_name' => $account->name,
        'amount' => '12',
        'note' => 'Coffee',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('records', ['account_id' => $account->id, 'note' => 'Coffee', 'type' => 'expense']);
});

it('rejects a webhook record with invalid data', function () {
    $webhook = Webhook::factory()->create(['is_active' => true]);

    signedWebhookCall($this, $webhook, [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'account_name', 'amount', 'recorded_at']);
});

it('returns 401 for a signature that matches no webhook', function () {
    $account = Account::factory()->create();

    $this->postJson('/api/webhooks/records', ['signature' => str_repeat('0', 64)] + [
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

    signedWebhookCall($this, $webhook, [
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

    signedWebhookCall($this->actingAs($actor), $webhook, [
        'type' => 'expense',
        'account_name' => $account->name,
        'amount' => '12',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ], ['url' => "/api/webhooks/{$webhook->id}/test"])->assertOk()->assertJsonPath('data.amount', '12');

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

    signedWebhookCall($this->actingAs($actor), $webhook, [
        'type' => 'income',
        'account_name' => $account->name,
        'amount' => '5',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ], ['url' => "/api/webhooks/{$webhook->id}/test"])->assertStatus(409);
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

    signedWebhookCall($this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5']), $webhook, webhookPayload($account))->assertStatus(201);
    signedWebhookCall($this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77']), $webhook, ['note' => 'b'] + webhookPayload($account))->assertStatus(201);
});

it('rejects requests from other IPs when IPs are whitelisted', function () {
    $webhook = Webhook::factory()->create(['allowed_ips' => ['203.0.113.5', '198.51.100.0/24']]);
    $account = Account::factory()->create();

    signedWebhookCall($this->withServerVariables(['REMOTE_ADDR' => '203.0.113.6']), $webhook, webhookPayload($account))->assertForbidden();
    signedWebhookCall($this->withServerVariables(['REMOTE_ADDR' => '198.51.101.1']), $webhook, ['note' => 'b'] + webhookPayload($account))->assertForbidden();

    expect(Record::query()->count())->toBe(0);
});

it('checks the IP on the webhook test endpoint', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create(['allowed_ips' => ['203.0.113.5']]);
    $account = Account::factory()->create();

    signedWebhookCall($this->actingAs($actor), $webhook, webhookPayload($account) + ['ip' => '192.0.2.1'], ['url' => "/api/webhooks/{$webhook->id}/test"])->assertForbidden();
    signedWebhookCall($this->actingAs($actor), $webhook, webhookPayload($account) + ['ip' => '203.0.113.5'], ['url' => "/api/webhooks/{$webhook->id}/test"])->assertOk();
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

    signedWebhookCall($this, $webhook, webhookPayload($account))->assertForbidden();
    signedWebhookCall($this, $webhook, ['type' => 'income'] + webhookPayload($account))->assertStatus(201);
});

it('only accepts the allowed accounts', function () {
    $allowed = Account::factory()->create();
    $other = Account::factory()->create();
    $webhook = Webhook::factory()->create(['allowed_account_ids' => [$allowed->id]]);

    signedWebhookCall($this, $webhook, webhookPayload($other))->assertForbidden();
    signedWebhookCall($this, $webhook, webhookPayload($allowed))->assertStatus(201);
});

it('applies the type and account restrictions on the test endpoint', function () {
    $actor = User::factory()->create();
    $allowed = Account::factory()->create();
    $other = Account::factory()->create();
    $webhook = Webhook::factory()->create(['allowed_account_ids' => [$allowed->id], 'allowed_types' => ['expense']]);

    signedWebhookCall($this->actingAs($actor), $webhook, webhookPayload($other), ['url' => "/api/webhooks/{$webhook->id}/test"])->assertForbidden();
    signedWebhookCall($this->actingAs($actor), $webhook, ['type' => 'income'] + webhookPayload($allowed), ['url' => "/api/webhooks/{$webhook->id}/test"])->assertForbidden();
    signedWebhookCall($this->actingAs($actor), $webhook, webhookPayload($allowed), ['url' => "/api/webhooks/{$webhook->id}/test"])->assertOk();
});

it('finds the webhook by the token-keyed signature and rejects a missing or foreign one', function () {
    $webhook = Webhook::factory()->create();
    $other = Webhook::factory()->create();
    $payload = webhookPayload(Account::factory()->create());

    $this->postJson('/api/webhooks/records', $payload)->assertUnauthorized();
    $this->postJson('/api/webhooks/records', ['signature' => 'deadbeef'] + $payload)->assertUnauthorized();
    signedWebhookCall($this, $webhook, $payload, ['signature' => webhookSignature($payload + ['timestamp' => time(), 'nonce' => 'x'], 'not-a-token')])->assertUnauthorized();
    expect(Record::query()->count())->toBe(0);

    signedWebhookCall($this, $webhook, $payload)->assertStatus(201);
    expect(WebhookLog::query()->where('webhook_id', $webhook->id)->count())->toBe(1)
        ->and(WebhookLog::query()->where('webhook_id', $other->id)->count())->toBe(0);
});

it('signs the timestamp, nonce and the six record fields', function () {
    $webhook = Webhook::factory()->create();
    $account = Account::factory()->create();
    $payload = webhookPayload($account) + ['note' => 'Coffee'];

    $changes = ['type' => 'income', 'account_name' => 'Other', 'amount' => '1', 'note' => 'Tea', 'recorded_at' => '2026-02-01T00:00:00+07:00', 'category_id' => 1, 'timestamp' => time() - 10, 'nonce' => Str::random(32)];

    foreach ($changes as $field => $value) {
        signedWebhookCall($this, $webhook, $payload, ['signed' => [$field => $value]])->assertUnauthorized();
    }

    expect(Record::query()->count())->toBe(0);

    signedWebhookCall($this, $webhook, $payload)->assertStatus(201);
});

it('rejects a missing, short or malformed nonce and timestamp', function () {
    $webhook = Webhook::factory()->create();
    $payload = webhookPayload(Account::factory()->create());

    foreach ([['nonce' => null], ['nonce' => 'short'], ['nonce' => str_repeat('a', 129)], ['timestamp' => null], ['timestamp' => 'yesterday']] as $overrides) {
        signedWebhookCall($this, $webhook, $payload, $overrides)->assertUnauthorized();
    }

    expect(Record::query()->count())->toBe(0);
});

it('rejects a request whose timestamp is outside the allowed window', function () {
    $webhook = Webhook::factory()->create();
    $payload = webhookPayload(Account::factory()->create());

    signedWebhookCall($this, $webhook, $payload, ['timestamp' => time() - 600])->assertUnauthorized();
    signedWebhookCall($this, $webhook, $payload, ['timestamp' => time() + 600])->assertUnauthorized();
    expect(Record::query()->count())->toBe(0);

    signedWebhookCall($this, $webhook, $payload, ['timestamp' => time() - 240])->assertStatus(201);
});

it('rejects a replayed nonce and accepts an identical record with a new nonce', function () {
    $webhook = Webhook::factory()->create();
    $payload = webhookPayload(Account::factory()->create());
    $nonce = Str::random(32);
    $timestamp = time();

    signedWebhookCall($this, $webhook, $payload, ['nonce' => $nonce, 'timestamp' => $timestamp])->assertStatus(201);
    signedWebhookCall($this, $webhook, $payload, ['nonce' => $nonce, 'timestamp' => $timestamp])->assertUnauthorized();
    expect(Record::query()->count())->toBe(1);

    signedWebhookCall($this, $webhook, $payload)->assertStatus(201);
    expect(Record::query()->count())->toBe(2);
});
it('only accepts income and expense records, and ignores fields that are not signed', function () {
    $webhook = Webhook::factory()->create();
    $account = Account::factory()->create();
    $other = Account::factory()->create();

    signedWebhookCall($this, $webhook, ['type' => 'transfer', 'to_account_id' => $other->id] + webhookPayload($account))
        ->assertStatus(422)->assertJsonValidationErrors(['type']);

    signedWebhookCall($this, $webhook, ['to_account_id' => $other->id, 'user_id' => 99] + webhookPayload($account))->assertStatus(201);
    expect(Record::query()->sole()->account_id)->toBe($account->id);
});

it('does not log the signature', function () {
    $webhook = Webhook::factory()->create();

    signedWebhookCall($this, $webhook, webhookPayload(Account::factory()->create()))->assertStatus(201);

    expect(WebhookLog::query()->sole()->payload)->not->toHaveKey('signature');
});

it('requires the same signed request on the test endpoint', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create();
    $other = Webhook::factory()->create();
    $payload = webhookPayload(Account::factory()->create());
    $url = "/api/webhooks/{$webhook->id}/test";

    $this->actingAs($actor)->postJson($url, $payload)->assertUnauthorized();
    signedWebhookCall($this->actingAs($actor), $webhook, $payload, ['url' => $url, 'signature' => 'deadbeef'])->assertUnauthorized();
    signedWebhookCall($this->actingAs($actor), $webhook, $payload, ['url' => $url, 'signed' => ['amount' => '1']])->assertUnauthorized();
    signedWebhookCall($this->actingAs($actor), $webhook, $payload, ['url' => $url, 'timestamp' => time() - 600])->assertUnauthorized();
    signedWebhookCall($this->actingAs($actor), $webhook, $payload, ['url' => $url, 'nonce' => 'short'])->assertUnauthorized();
    signedWebhookCall($this->actingAs($actor), $other, $payload, ['url' => $url])->assertUnauthorized();

    signedWebhookCall($this->actingAs($actor), $webhook, $payload, ['url' => $url])->assertOk();
    expect(Record::query()->count())->toBe(0);
});
