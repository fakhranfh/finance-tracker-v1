<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * CLI-only fixture seeding for Playwright E2E specs (tests/playwright/**).
 *
 * This is deliberately not an HTTP endpoint or a page "Generate data"
 * button — per .ai/rules/dev-autofill.md, that dev-UI feature is scoped to
 * pages that display a table/list of data, which none of the auth pages
 * are. E2E specs still need real backing records/tokens, so this command
 * seeds them directly via the CLI instead.
 */
#[AsCommand(name: 'test:seed-e2e-fixture')]
class SeedE2eFixture extends Command
{
    protected $signature = 'test:seed-e2e-fixture {kind : "user", "admin" or "reset-token"}';

    protected $description = 'Seed a fixture record for Playwright E2E specs and print it as JSON (local/testing only).';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This command is only available in local/testing environments.');

            return self::FAILURE;
        }

        $plainPassword = 'Password123!';
        $user = User::factory()->when($this->argument('kind') === 'admin', fn ($factory) => $factory->admin())->create([
            'password' => bcrypt($plainPassword),
        ]);

        $payload = match ($this->argument('kind')) {
            'user', 'admin' => [
                'email' => $user->email,
                'password' => $plainPassword,
            ],
            'reset-token' => [
                'email' => $user->email,
                'token' => Password::broker()->createToken($user),
            ],
            default => null,
        };

        if ($payload === null) {
            $this->error('Unknown fixture kind. Use "user", "admin" or "reset-token".');

            return self::FAILURE;
        }

        $this->line(json_encode($payload));

        return self::SUCCESS;
    }
}
