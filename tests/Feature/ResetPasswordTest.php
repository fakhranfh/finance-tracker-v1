<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'New-Password123!',
        'password_confirmation' => 'New-Password123!',
    ]);

    $response->assertStatus(200);

    expect(Hash::check('New-Password123!', $user->fresh()->password))->toBeTrue();
});

it('rejects an invalid reset token', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/reset-password', [
        'token' => 'invalid-token',
        'email' => 'jane@example.com',
        'password' => 'New-Password123!',
        'password_confirmation' => 'New-Password123!',
    ]);

    $response->assertStatus(422);

    expect(Hash::check('New-Password123!', $user->fresh()->password))->toBeFalse();
});
