<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Hash;

it('rejects a guest from listing users', function () {
    $this->getJson('/api/users')->assertStatus(401);
});

it('lists users via the api', function () {
    $actor = User::factory()->create();
    User::factory()->count(3)->create();

    $response = $this->actingAs($actor)->getJson('/api/users');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'name', 'email', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(4);
});

it('creates a user via the api', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.email', 'jane@example.com');

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();
    expect(Hash::check('Password123!', $user->password))->toBeTrue();
});

it('rejects creating a user with a duplicate email', function () {
    $actor = User::factory()->create();
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->actingAs($actor)->postJson('/api/users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

it('updates a user via the api without changing the password when omitted', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create(['name' => 'Old Name']);
    $originalPassword = $target->password;

    $response = $this->actingAs($actor)->putJson("/api/users/{$target->id}", [
        'name' => 'New Name',
        'email' => $target->email,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.name', 'New Name');

    $target->refresh();
    expect($target->password)->toBe($originalPassword);
});

it('updates a user password via the api when provided', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($actor)->putJson("/api/users/{$target->id}", [
        'name' => $target->name,
        'email' => $target->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertOk();

    $target->refresh();
    expect(Hash::check('NewPassword123!', $target->password))->toBeTrue();
});

it('deletes a user via the api', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($actor)->deleteJson("/api/users/{$target->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

it('generates sample users via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/users/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(User::query()->count())->toBe(6);
});

it('rejects generating sample users outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->postJson('/api/users/generate', ['count' => 5]);

    $response->assertNotFound();
});
