<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => UserFactory::ownerId(),
            'name' => fake()->randomElement(['Tunai', 'BCA', 'BRI', 'BNI', 'Mandiri', 'Jago', 'GoPay', 'OVO', 'DANA', 'ShopeePay', 'Tabungan', 'Dana Darurat']).' '.fake()->unique()->numberBetween(1, 9999),
            'last_balance' => fake()->numberBetween(10, 1000) * 1000,
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(array_keys(config('account.icons'))),
        ];
    }
}
