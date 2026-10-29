<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sample = fake()->randomElement(config('goal.samples'));
        $target = $sample['target'];

        return [
            'user_id' => fn () => UserFactory::ownerId(),
            'name' => $sample['name'].' '.fake()->unique()->numberBetween(1, 99999),
            'target_amount' => $target,
            'saved_amount' => fake()->numberBetween(0, $target / 1000) * 1000,
            'desired_date' => fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
            'color' => $sample['color'],
            'icon' => $sample['icon'],
            'status' => fake()->randomElement(GoalStatus::cases()),
            'note' => fake()->boolean() ? $sample['note'] : null,
        ];
    }
}
