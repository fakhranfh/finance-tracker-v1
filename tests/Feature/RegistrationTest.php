<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a new user via the api', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.email', 'jane@example.com');
    $response->assertJsonStructure(['token']);

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
    ]);

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect(Hash::check('Password123!', $user->password))->toBeTrue();
    expect($user->tokens()->count())->toBe(1);
});

it('rejects registration with a mismatched password confirmation', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'different',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');

    $this->assertDatabaseMissing('users', [
        'email' => 'jane@example.com',
    ]);
});

it('closes registration once a user exists', function () {
    User::factory()->create();

    $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertForbidden();

    $this->get('/register')->assertRedirect(route('login'));
});
