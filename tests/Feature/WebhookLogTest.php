<?php

use App\Models\Account;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookLog;

function logPayload(Account $account): array
{
    return [
        'type' => 'expense',
        'account_name' => $account->name,
        'amount' => '5',
        'recorded_at' => '2026-01-15T10:30:00+07:00',
    ];
}

it('logs a successful webhook call with the created record', function () {
    $webhook = Webhook::factory()->create();
    $account = Account::factory()->create();

    $recordId = signedWebhookCall($this, $webhook, logPayload($account), [], ['Origin' => 'https://example.com'])
        ->assertStatus(201)
        ->json('data.id');

    $log = WebhookLog::query()->sole();
    expect($log->webhook_id)->toBe($webhook->id)
        ->and($log->status_code)->toBe(201)
        ->and($log->origin)->toBe('https://example.com')
        ->and($log->record_id)->toBe($recordId)
        ->and($log->payload['account_name'])->toBe($account->name);
});

it('logs rejected webhook calls with their status', function () {
    $account = Account::factory()->create();
    $active = Webhook::factory()->create();
    $inactive = Webhook::factory()->create(['is_active' => false]);
    $restricted = Webhook::factory()->create(['allowed_ips' => ['203.0.113.5']]);

    signedWebhookCall($this, $active, [])->assertStatus(422);
    signedWebhookCall($this, $inactive, logPayload($account))->assertNotFound();
    signedWebhookCall($this, $restricted, logPayload($account))->assertForbidden();

    expect(WebhookLog::query()->where('webhook_id', $active->id)->value('status_code'))->toBe(422)
        ->and(WebhookLog::query()->where('webhook_id', $inactive->id)->value('status_code'))->toBe(404)
        ->and(WebhookLog::query()->where('webhook_id', $restricted->id)->value('status_code'))->toBe(403);
});

it('does not log calls with an unknown signature', function () {
    $this->postJson('/api/webhooks/records', ['signature' => 'unknown'])->assertUnauthorized();
    $this->postJson('/api/webhooks/records', [])->assertUnauthorized();

    expect(WebhookLog::query()->count())->toBe(0);
});

it('keeps only the latest 500 logs per webhook', function () {
    $webhook = Webhook::factory()->create();
    $account = Account::factory()->create();

    foreach (range(1, 502) as $i) {
        WebhookLog::query()->create(['webhook_id' => $webhook->id, 'status_code' => 201]);
    }

    signedWebhookCall($this, $webhook, logPayload($account))->assertStatus(201);

    expect(WebhookLog::query()->where('webhook_id', $webhook->id)->count())->toBe(500);
});

it('lists a webhook logs newest first and only its own', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create();
    $other = Webhook::factory()->create();
    $first = WebhookLog::query()->create(['webhook_id' => $webhook->id, 'status_code' => 201, 'message' => 'first']);
    $second = WebhookLog::query()->create(['webhook_id' => $webhook->id, 'status_code' => 422, 'message' => 'second']);
    WebhookLog::query()->create(['webhook_id' => $other->id, 'status_code' => 201]);

    $response = $this->actingAs($actor)->getJson("/api/webhooks/{$webhook->id}/logs");

    $response->assertOk()->assertJsonStructure(['data' => [['id', 'status_code', 'ip', 'origin', 'message', 'payload', 'record_id', 'created_at']]]);
    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$second->id, $first->id]);
});

it('searches a webhook logs', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create();
    WebhookLog::query()->create(['webhook_id' => $webhook->id, 'status_code' => 422, 'message' => 'amount is required']);
    WebhookLog::query()->create(['webhook_id' => $webhook->id, 'status_code' => 201]);

    expect($this->actingAs($actor)->getJson("/api/webhooks/{$webhook->id}/logs?search=amount")->json('data'))->toHaveCount(1)
        ->and($this->actingAs($actor)->getJson("/api/webhooks/{$webhook->id}/logs?search=201")->json('data'))->toHaveCount(1);
});

it('clears a webhook logs without touching other webhooks', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create();
    $other = Webhook::factory()->create();
    WebhookLog::query()->create(['webhook_id' => $webhook->id, 'status_code' => 201]);
    WebhookLog::query()->create(['webhook_id' => $other->id, 'status_code' => 201]);

    $this->actingAs($actor)->deleteJson("/api/webhooks/{$webhook->id}/logs")->assertNoContent();

    expect(WebhookLog::query()->where('webhook_id', $webhook->id)->count())->toBe(0)
        ->and(WebhookLog::query()->where('webhook_id', $other->id)->count())->toBe(1);
});

it('deletes the logs together with their webhook', function () {
    $actor = User::factory()->create();
    $webhook = Webhook::factory()->create();
    WebhookLog::query()->create(['webhook_id' => $webhook->id, 'status_code' => 201]);

    $this->actingAs($actor)->deleteJson("/api/webhooks/{$webhook->id}")->assertNoContent();

    expect(WebhookLog::query()->count())->toBe(0);
});

it('rejects a guest from reading or clearing logs', function () {
    $webhook = Webhook::factory()->create();

    $this->getJson("/api/webhooks/{$webhook->id}/logs")->assertStatus(401);
    $this->deleteJson("/api/webhooks/{$webhook->id}/logs")->assertStatus(401);
});
