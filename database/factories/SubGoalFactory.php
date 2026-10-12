<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\SubGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubGoal>
 */
class SubGoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sample = fake()->randomElement(config('goal.sub_goal_samples'));

        return [
            'goal_id' => Goal::factory()->state(['status' => GoalStatus::Active]),
            'name' => $sample['name'],
            'target_amount' => $sample['target'],
            'saved_amount' => 0,
            'desired_date' => fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'is_done' => fake()->boolean(),
            'note' => fake()->boolean() ? $sample['note'] : null,
        ];
    }
}
