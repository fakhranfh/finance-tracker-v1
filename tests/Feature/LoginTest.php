<?php

use App\Models\User;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('logs in a user with valid credentials and stores their detected timezone', function () {
    Location::fake([
        '127.0.0.1' => Position::make(['timezone' => 'Asia/Jakarta']),
    ]);

    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
        'timezone' => null,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.email', 'jane@example.com');
    $response->assertJsonStructure(['token']);

    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->timezone)->toBe('Asia/Jakarta');
    expect($user->tokens()->count())->toBe(1);
});

it('rejects login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);

    $this->assertGuest();
});

it('rejects login for a non-existent user', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'missing@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);

    $this->assertGuest();
});
