<?php

use App\Models\User;

it('logs out an authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/logout');

    $response->assertStatus(200);

    expect($user->tokens()->count())->toBe(0);
});

it('rejects logout when not authenticated', function () {
    $response = $this->postJson('/api/logout');

    $response->assertStatus(401);
});
