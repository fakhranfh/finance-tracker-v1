<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

it('sends a verification email and logs in on register', function () {
    Notification::fake();

    $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(201);

    $user = User::where('email', 'jane@example.com')->firstOrFail();

    Notification::assertSentTo($user, VerifyEmail::class);
    $this->assertAuthenticatedAs($user);
});

it('redirects unverified users to the verification notice', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get('/email/verify')->assertOk();
});

it('redirects verified users away from the notice page', function () {
    $this->actingAs(User::factory()->create())->get('/email/verify')->assertRedirect(route('dashboard'));
});

it('verifies email via the signed link', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('enforces a one minute cooldown on resend', function () {
    Notification::fake();
    RateLimiter::clear('verification-email:1');

    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/email/verification-notification')
        ->assertOk()->assertJsonPath('retry_after', 60);

    $this->actingAs($user, 'sanctum')->postJson('/api/email/verification-notification')
        ->assertStatus(429)->assertJsonStructure(['retry_after']);

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
});
