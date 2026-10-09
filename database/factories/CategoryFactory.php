<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sample = fake()->randomElement(config('category.samples'));

        return [
            'name' => $sample['name'].' '.fake()->unique()->numberBetween(1, 99999),
            'type' => CategoryType::Expense,
            'color' => $sample['color'],
            'icon' => $sample['icon'],
        ];
    }

    public function income(): static
    {
        return $this->state(['type' => CategoryType::Income]);
    }
}
