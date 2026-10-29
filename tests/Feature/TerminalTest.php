<?php

use App\Models\Setting;
use App\Models\User;
use App\Notifications\TerminalPasswordResetNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Setting::create(['key' => 'terminal.password', 'value' => Hash::make('terminal-secret')]);
});

function terminalAdmin(): User
{
    return User::factory()->admin()->create();
}

function unlockTerminal(): void
{
    test()->actingAs(terminalAdmin());
    test()->postJson('/api/terminal/unlock', ['password' => 'terminal-secret'])->assertOk();
}

it('redirects a guest away from the terminal page', function () {
    $this->get('/terminal')->assertRedirect('/login');
});

it('forbids a regular user from the terminal page and api', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/terminal')->assertForbidden();
    $this->actingAs($user)->getJson('/api/terminal')->assertForbidden();
    $this->actingAs($user)->postJson('/api/terminal/unlock', ['password' => 'terminal-secret'])->assertForbidden();
    $this->actingAs($user)->postJson('/api/terminal/run', ['command' => 'php artisan list'])->assertForbidden();
    $this->actingAs($user)->putJson('/api/terminal/password', [])->assertForbidden();
});

it('renders the terminal page for an admin', function () {
    $this->actingAs(terminalAdmin())->get('/terminal')->assertOk();
});

it('reports the terminal as locked until the password is entered', function () {
    $this->actingAs(terminalAdmin())->getJson('/api/terminal')
        ->assertOk()
        ->assertJsonPath('data.configured', true)
        ->assertJsonPath('data.unlocked', false);
});

it('rejects a wrong password', function () {
    $this->actingAs(terminalAdmin())->postJson('/api/terminal/unlock', ['password' => 'nope'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('requires a password to unlock', function () {
    $this->actingAs(terminalAdmin())->postJson('/api/terminal/unlock', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('unlocks with the password stored in the database', function () {
    $this->actingAs(terminalAdmin())->postJson('/api/terminal/unlock', ['password' => 'terminal-secret'])
        ->assertOk()
        ->assertJsonPath('data.unlocked', true);

    $this->getJson('/api/terminal')->assertJsonPath('data.unlocked', true);
});

it('needs a password to be set before anything can be unlocked', function () {
    Setting::query()->delete();
    $admin = terminalAdmin();

    $this->actingAs($admin)->getJson('/api/terminal')->assertJsonPath('data.configured', false);
    $this->actingAs($admin)->postJson('/api/terminal/unlock', ['password' => 'anything'])->assertStatus(422);
    $this->actingAs($admin)->postJson('/api/terminal/run', ['command' => 'php artisan list'])->assertStatus(423);
});

it('sets the first password without a current one and unlocks', function () {
    Setting::query()->delete();

    $this->actingAs(terminalAdmin())->putJson('/api/terminal/password', [
        'password' => 'NewTerminal1!',
        'password_confirmation' => 'NewTerminal1!',
    ])->assertOk()->assertJsonPath('data.configured', true)->assertJsonPath('data.unlocked', true);

    expect(Hash::check('NewTerminal1!', Setting::where('key', 'terminal.password')->value('value')))->toBeTrue();
});

it('changes the password when the current one is right', function () {
    $this->actingAs(terminalAdmin())->putJson('/api/terminal/password', [
        'current_password' => 'terminal-secret',
        'password' => 'NewTerminal1!',
        'password_confirmation' => 'NewTerminal1!',
    ])->assertOk();

    $this->deleteJson('/api/terminal')->assertOk();
    $this->postJson('/api/terminal/unlock', ['password' => 'terminal-secret'])->assertStatus(422);
    $this->postJson('/api/terminal/unlock', ['password' => 'NewTerminal1!'])->assertOk();
});

it('rejects a password change with a wrong current password', function () {
    $this->actingAs(terminalAdmin())->putJson('/api/terminal/password', [
        'current_password' => 'wrong',
        'password' => 'NewTerminal1!',
        'password_confirmation' => 'NewTerminal1!',
    ])->assertStatus(422)->assertJsonValidationErrors('current_password');
});

it('requires the current password and a strong confirmed new one', function () {
    $this->actingAs(terminalAdmin())->putJson('/api/terminal/password', [
        'password' => 'weak',
        'password_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors(['current_password', 'password']);
});

it('refuses to run commands while locked', function () {
    $this->actingAs(terminalAdmin())->postJson('/api/terminal/run', ['command' => 'php artisan list'])
        ->assertStatus(423);
});

it('runs a shell command once unlocked', function () {
    unlockTerminal();

    $this->postJson('/api/terminal/run', ['command' => 'php artisan list'])
        ->assertOk()
        ->assertJsonPath('data.exit_code', 0)
        ->assertJsonPath('data.command', 'php artisan list')
        ->assertJsonPath('data.output', fn (string $output) => str_contains($output, 'migrate'));
});

it('runs commands that are not artisan', function () {
    unlockTerminal();

    $this->postJson('/api/terminal/run', ['command' => 'php -r "echo 6*7;"'])
        ->assertOk()
        ->assertJsonPath('data.exit_code', 0)
        ->assertJsonPath('data.output', '42');
});

it('reports a failing command without failing the request', function () {
    unlockTerminal();

    $this->postJson('/api/terminal/run', ['command' => 'php -r "fwrite(STDERR, \\"boom\\"); exit(3);"'])
        ->assertOk()
        ->assertJsonPath('data.exit_code', 3)
        ->assertJsonPath('data.output', 'boom');
});

it('stops a command that runs too long', function () {
    config(['terminal.timeout' => 1]);
    unlockTerminal();

    $this->postJson('/api/terminal/run', ['command' => 'php -r "sleep(5);"'])
        ->assertOk()
        ->assertJsonPath('data.exit_code', 124);
});

it('requires a command', function () {
    unlockTerminal();

    $this->postJson('/api/terminal/run', ['command' => ''])->assertStatus(422)->assertJsonValidationErrors('command');
});

it('locks the terminal again', function () {
    unlockTerminal();

    $this->deleteJson('/api/terminal')->assertOk()->assertJsonPath('data.unlocked', false);
    $this->postJson('/api/terminal/run', ['command' => 'php artisan list'])->assertStatus(423);
});

it('falls back to artisan-only mode when no shell can be started', function () {
    config(['terminal.shell' => false]);
    unlockTerminal();

    $this->postJson('/api/terminal/run', ['command' => 'php artisan list'])
        ->assertOk()
        ->assertJsonPath('data.exit_code', 0)
        ->assertJsonPath('data.output', fn (string $output) => str_contains($output, 'migrate'));

    $this->postJson('/api/terminal/run', ['command' => 'git status'])
        ->assertOk()
        ->assertJsonPath('data.exit_code', 1)
        ->assertJsonPath('data.output', __('terminal.errors.artisan_only'));

    $this->postJson('/api/terminal/run', ['command' => 'artisan serve'])
        ->assertOk()
        ->assertJsonPath('data.exit_code', 1)
        ->assertJsonPath('data.output', __('terminal.errors.blocked'));
});

function terminalResetToken(User $admin): string
{
    Notification::fake();
    test()->actingAs($admin)->postJson('/api/terminal/forgot')->assertOk();

    $token = null;
    Notification::assertSentTo($admin, TerminalPasswordResetNotification::class, function ($notification) use (&$token) {
        $url = (string) (new ReflectionProperty($notification, 'url'))->getValue($notification);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $token = $query['reset'];

        return str_starts_with($url, url('/terminal'));
    });

    return $token;
}

it('emails the admin a reset link', function () {
    $admin = terminalAdmin();

    expect(terminalResetToken($admin))->toBeString()->toHaveLength(64);
});

it('cannot send a reset link before a password exists', function () {
    Setting::query()->delete();

    $this->actingAs(terminalAdmin())->postJson('/api/terminal/forgot')->assertStatus(422);
});

it('resets the password with the emailed token, once', function () {
    $admin = terminalAdmin();
    $token = terminalResetToken($admin);

    $this->postJson('/api/terminal/reset', [
        'token' => $token,
        'password' => 'ResetTerminal1!',
        'password_confirmation' => 'ResetTerminal1!',
    ])->assertOk()->assertJsonPath('data.unlocked', true);

    $this->deleteJson('/api/terminal')->assertOk();
    $this->postJson('/api/terminal/unlock', ['password' => 'terminal-secret'])->assertStatus(422);
    $this->postJson('/api/terminal/unlock', ['password' => 'ResetTerminal1!'])->assertOk();

    $this->postJson('/api/terminal/reset', [
        'token' => $token,
        'password' => 'AnotherTerminal1!',
        'password_confirmation' => 'AnotherTerminal1!',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('rejects a wrong or expired reset token', function () {
    $admin = terminalAdmin();
    $token = terminalResetToken($admin);
    $payload = ['password' => 'ResetTerminal1!', 'password_confirmation' => 'ResetTerminal1!'];

    $this->postJson('/api/terminal/reset', ['token' => 'wrong'] + $payload)->assertStatus(422);

    $this->travel(61)->minutes();
    $this->postJson('/api/terminal/reset', ['token' => $token] + $payload)->assertStatus(422);
});

it('keeps the reset endpoints away from regular users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/terminal/forgot')->assertForbidden();
    $this->actingAs($user)->postJson('/api/terminal/reset', [])->assertForbidden();
});

it('finds php inside the terminal shell', function () {
    unlockTerminal();

    $this->postJson('/api/terminal/run', ['command' => 'php -v'])
        ->assertOk()
        ->assertJsonPath('data.exit_code', 0)
        ->assertJsonPath('data.output', fn (string $output) => str_starts_with($output, 'PHP '));
});
