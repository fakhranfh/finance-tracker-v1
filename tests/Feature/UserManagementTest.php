<?php

use App\Enums\RoleName;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

function admin(): User
{
    return User::factory()->admin()->create();
}

it('rejects a guest from listing users', function () {
    $this->getJson('/api/users')->assertStatus(401);
});

it('rejects a regular user from the users api and pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/users')->assertForbidden();
    $this->actingAs($user)->postJson('/api/users', [])->assertForbidden();
    $this->actingAs($user)->deleteJson('/api/users', ['all' => true])->assertForbidden();
    $this->actingAs($user)->get('/users')->assertForbidden();
});

it('gives every new user the user role', function () {
    $user = User::factory()->create();

    expect($user->hasRole(RoleName::User->value))->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});

it('lists users with their role via the api', function () {
    $actor = admin();
    User::factory()->count(2)->create();

    $response = $this->actingAs($actor)->getJson('/api/users');

    $response->assertOk();
    $response->assertJsonStructure(['data' => [['id', 'name', 'email', 'role', 'role_label', 'created_at']]]);
    expect($response->json('data'))->toHaveCount(3);
    expect(collect($response->json('data'))->pluck('role')->sort()->values()->all())->toBe(['admin', 'user', 'user']);
});

it('searches users via the api', function () {
    $actor = admin();
    User::factory()->create(['name' => 'Findable entry']);
    User::factory()->create(['name' => 'Other']);

    $response = $this->actingAs($actor)->getJson('/api/users?search=Findable');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a user with a role via the api', function () {
    $actor = admin();

    $response = $this->actingAs($actor)->postJson('/api/users', [
        'name' => 'Sample name',
        'email' => 'sample@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'admin',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Sample name');
    $response->assertJsonPath('data.role', 'admin');

    $created = User::query()->where('email', 'sample@example.com')->firstOrFail();
    expect($created->isAdmin())->toBeTrue()->and($created->hasVerifiedEmail())->toBeTrue();
});

it('rejects creating a user with missing or invalid fields', function () {
    $actor = admin();

    $this->actingAs($actor)->postJson('/api/users', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);

    $this->actingAs($actor)->postJson('/api/users', [
        'name' => 'X',
        'email' => $actor->email,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'role' => 'superuser',
    ])->assertStatus(422)->assertJsonValidationErrors(['email', 'role']);
});

it('shows a user via the api', function () {
    $actor = admin();
    $target = User::factory()->create();

    $this->actingAs($actor)->getJson("/api/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.role', 'user');
});

it('updates a user and changes their role via the api', function () {
    $actor = admin();
    $target = User::factory()->create();

    $this->actingAs($actor)->putJson("/api/users/{$target->id}", [
        'name' => 'Updated name',
        'email' => 'updated@example.com',
        'role' => 'admin',
    ])->assertOk()->assertJsonPath('data.name', 'Updated name')->assertJsonPath('data.role', 'admin');

    expect($target->fresh()->isAdmin())->toBeTrue();
});

it('keeps the password when updating without one', function () {
    $actor = admin();
    $target = User::factory()->create();
    $hash = $target->password;

    $this->actingAs($actor)->putJson("/api/users/{$target->id}", [
        'name' => 'Updated name',
        'email' => $target->email,
        'role' => 'user',
    ])->assertOk();

    expect($target->fresh()->password)->toBe($hash);
});

it('does not let an admin demote themselves', function () {
    $actor = admin();

    $this->actingAs($actor)->putJson("/api/users/{$actor->id}", [
        'name' => 'Still admin',
        'email' => $actor->email,
        'role' => 'user',
    ])->assertOk();

    expect($actor->fresh()->isAdmin())->toBeTrue();
});

it('deletes a user via the api', function () {
    $actor = admin();
    $target = User::factory()->create();

    $this->actingAs($actor)->deleteJson("/api/users/{$target->id}")->assertNoContent();
    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

it('does not let an admin delete themselves', function () {
    $actor = admin();

    $this->actingAs($actor)->deleteJson("/api/users/{$actor->id}")->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $actor->id]);
});

it('bulk deletes users by id but never the signed-in admin', function () {
    $actor = admin();
    $targets = User::factory()->count(3)->create();

    $this->actingAs($actor)
        ->deleteJson('/api/users', ['ids' => [...$targets->take(2)->pluck('id')->all(), $actor->id]])
        ->assertNoContent();

    expect(User::query()->count())->toBe(2);
    $this->assertDatabaseHas('users', ['id' => $actor->id]);
});

it('bulk deletes every other user when all is set', function () {
    $actor = admin();
    User::factory()->count(3)->create();

    $this->actingAs($actor)->deleteJson('/api/users', ['all' => true])->assertNoContent();

    expect(User::query()->pluck('id')->all())->toBe([$actor->id]);
});

it('deletes the data of a deleted user', function () {
    $actor = admin();
    $target = User::factory()->create();
    $this->actingAs($target);
    $account = Account::factory()->create();

    $this->actingAs($actor)->deleteJson("/api/users/{$target->id}")->assertNoContent();

    $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
});

it('generates sample users via the api in the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');

    $actor = admin();

    $response = $this->actingAs($actor)->postJson('/api/users/generate', ['count' => 5]);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    expect(User::query()->count())->toBe(6);
});

it('rejects generating sample users outside the local environment', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'production');

    $this->actingAs(admin())->postJson('/api/users/generate', ['count' => 5])->assertNotFound();
});

it('shows the users menu to admins only', function () {
    $this->actingAs(admin())->get('/dashboard')->assertOk()->assertSee(route('users.index'));
    $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertDontSee(route('users.index'));
});

it('makes the first registered user an admin', function () {
    $this->postJson('/api/register', [
        'name' => 'First',
        'email' => 'first@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertCreated();

    expect(User::query()->where('email', 'first@example.com')->firstOrFail()->isAdmin())->toBeTrue();
});

it('creates an admin from the console', function () {
    $this->artisan('admin:create', ['email' => 'boss@example.com', '--password' => 'Password123!'])->assertSuccessful();

    expect(User::query()->where('email', 'boss@example.com')->firstOrFail()->isAdmin())->toBeTrue();
});

it('promotes an existing user from the console', function () {
    $user = User::factory()->create();

    $this->artisan('admin:create', ['email' => $user->email])->assertSuccessful();

    expect($user->fresh()->isAdmin())->toBeTrue();
});
