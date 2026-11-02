<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CycleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CycleType>
 */
class CycleTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    /**
     * A sample item name for the category, with a purchase history. A name already in use in the category is
     * numbered ("Serum 2") rather than duplicated.
     */
    public static function createSample(Category $category): CycleType
    {
        $taken = CycleType::query()->where('category_id', $category->id)->pluck('name')->flip();
        $samples = collect(config('cycle.type_samples'))->shuffle();

        for ($round = 1; ; $round++) {
            foreach ($samples as $sample) {
                $name = $round === 1 ? $sample['name'] : "{$sample['name']} {$round}";

                if (! $taken->has($name)) {
                    $type = CycleType::factory()->create(['category_id' => $category->id, 'name' => $name, 'icon' => $sample['icon']]);

                    CycleEntryFactory::createChain($type, fake()->numberBetween(3, 5));

                    return $type;
                }
            }
        }
    }

    public function definition(): array
    {
        $sample = fake()->randomElement(config('cycle.type_samples'));

        return [
            'user_id' => fn () => UserFactory::ownerId(),
            'category_id' => Category::factory(),
            'name' => $sample['name'].' '.fake()->unique()->numberBetween(1, 99999),
            'icon' => $sample['icon'],
        ];
    }
}
