<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Admins only manage users and cannot see anyone's finances, so they are not made from the app's own
 * registration: the first account registered keeps being a regular user. Use this to add one on purpose.
 */
#[AsCommand(name: 'admin:create')]
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
        {email : Email address of the admin}
        {--name=Admin : Display name}
        {--password= : Password (asked for when left out)}';

    protected $description = 'Create an admin user, or promote the existing user with that email.';

    public function handle(UserManagementService $users): int
    {
        $email = (string) $this->argument('email');
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $existing->syncRoles(RoleName::Admin->value);
            $this->info("{$email} is now an admin.");

            return self::SUCCESS;
        }

        $password = (string) ($this->option('password') ?: $this->secret('Password'));

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['email'], 'password' => ['required', Password::defaults()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $users->create([
            'name' => (string) $this->option('name'),
            'email' => $email,
            'password' => $password,
            'role' => RoleName::Admin->value,
        ]);

        $this->info("Admin {$email} created.");

        return self::SUCCESS;
    }
}
