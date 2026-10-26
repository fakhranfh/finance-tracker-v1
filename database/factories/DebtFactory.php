<?php

namespace Database\Factories;

use App\Enums\DebtType;
use App\Models\Debt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(DebtType::cases()),
            'name' => fake()->words(3, true),
            'amount' => fake()->randomFloat(2, 1, 1000),
            'paid_amount' => fake()->randomFloat(2, 1, 1000),
            'due_date' => fake()->date('Y-m-d'),
            'notes' => fake()->paragraph(),
            'is_paid' => fake()->boolean(),
        ];
    }
}
