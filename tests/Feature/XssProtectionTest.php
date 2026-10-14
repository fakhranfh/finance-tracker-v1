<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('escapes script tags in api responses', function () {
    $user = User::factory()->create(['name' => '<script>alert(1)</script>']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/profile');

    expect($response->getContent())->not->toContain('<script');
});

it('sends a content security policy header with a nonce', function () {
    $csp = $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("object-src 'none'")->toContain('nonce-');
});

it('expires sanctum tokens after one hour', function () {
    expect(config('sanctum.expiration'))->toBe(60);
});
