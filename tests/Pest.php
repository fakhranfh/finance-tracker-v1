<?php

use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * The hex HMAC-SHA256, keyed by the webhook token, of timestamp, nonce and the six
 * record fields in this order as "name=value" pairs joined with "&" (missing ones empty).
 *
 * @param  array<string, mixed>  $fields
 */
function webhookSignature(array $fields, string $token): string
{
    $pairs = array_map(
        fn (string $name) => $name.'='.($fields[$name] ?? ''),
        ['timestamp', 'nonce', 'type', 'account_name', 'category_name', 'amount', 'note', 'recorded_at'],
    );

    return hash_hmac('sha256', implode('&', $pairs), $token);
}

/**
 * POST a signed request to the public webhook endpoint. The timestamp and nonce travel in the
 * X-Webhook-Timestamp and X-Webhook-Nonce headers. $overrides: 'timestamp' and 'nonce' replace
 * them (null leaves the header out), 'signature' replaces the signature, 'signed' replaces
 * values only in what gets signed and 'url' posts somewhere else (e.g. the dry-run endpoint).
 *
 * @param  array<string, mixed>  $payload
 * @param  array<string, mixed>  $overrides
 * @param  array<string, string>  $headers
 */
function signedWebhookCall(object $test, Webhook $webhook, array $payload, array $overrides = [], array $headers = [])
{
    $timestamp = array_key_exists('timestamp', $overrides) ? $overrides['timestamp'] : time();
    $nonce = array_key_exists('nonce', $overrides) ? $overrides['nonce'] : Str::random(32);
    $signature = $overrides['signature']
        ?? webhookSignature(array_merge($payload, ['timestamp' => $timestamp, 'nonce' => $nonce], $overrides['signed'] ?? []), $webhook->token);

    $headers += array_filter(['X-Webhook-Timestamp' => $timestamp, 'X-Webhook-Nonce' => $nonce], fn ($value) => $value !== null);

    return $test->postJson($overrides['url'] ?? '/api/webhooks/records', $payload + ['signature' => $signature], array_map('strval', $headers));
}
