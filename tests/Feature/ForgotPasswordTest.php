<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('sends a password reset link for an existing user', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/forgot-password', [
        'email' => 'jane@example.com',
    ]);

    $response->assertStatus(200);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('returns a validation error for an unknown email', function () {
    $response = $this->postJson('/api/forgot-password', [
        'email' => 'missing@example.com',
    ]);

    $response->assertStatus(422);
});
