<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('redirects a guest away from the profile page', function () {
    $this->get('/profile')->assertRedirect('/login');
});

it('renders the profile page for an authenticated user', function () {
    $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
});

it('rejects a guest from the profile api', function () {
    $this->getJson('/api/profile')->assertStatus(401);
    $this->putJson('/api/profile', [])->assertStatus(401);
});

it('shows the authenticated user via the api', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('updates name and email without touching the password', function () {
    $user = User::factory()->create(['password' => Hash::make('OldPassword1!')]);

    $this->actingAs($user)->putJson('/api/profile', [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ])->assertOk()->assertJsonPath('data.name', 'New Name');

    $user->refresh();
    expect($user->email)->toBe('new@example.com')
        ->and(Hash::check('OldPassword1!', $user->password))->toBeTrue();
});

it('requires the new email to be verified again', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/profile', [
        'name' => $user->name,
        'email' => 'changed@example.com',
    ])->assertOk()->assertJsonPath('data.email_verified', false);

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('keeps the email verified when it is unchanged', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/profile', [
        'name' => 'Other Name',
        'email' => $user->email,
    ])->assertOk()->assertJsonPath('data.email_verified', true);

    Notification::assertNothingSent();
});

it('changes the password when the current password is correct', function () {
    $user = User::factory()->create(['password' => Hash::make('OldPassword1!')]);

    $this->actingAs($user)->putJson('/api/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'current_password' => 'OldPassword1!',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertOk();

    expect(Hash::check('NewPassword1!', $user->refresh()->password))->toBeTrue();
});

it('rejects a password change with a wrong current password', function () {
    $user = User::factory()->create(['password' => Hash::make('OldPassword1!')]);

    $this->actingAs($user)->putJson('/api/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'current_password' => 'WrongPassword1!',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertStatus(422)->assertJsonValidationErrors('current_password');
});

it('rejects an email already taken by another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->putJson('/api/profile', [
        'name' => $user->name,
        'email' => $other->email,
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});
