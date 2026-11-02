<?php

namespace App\Traits\CycleCategory;

use App\Enums\CategoryType;
use App\Models\Category;
use Database\Factories\CycleTypeFactory;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the Usage Cycle categories list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample expense categories from the presets in config/category.php, each with a few item names and
     * their purchase histories. A preset already in use is numbered ("Belanja 2") rather than duplicated.
     *
     * @return Collection<int, Category>
     */
    protected function generateSampleCategories(int $count = 3): Collection
    {
        $samples = collect(config('category.samples'))->where('type', CategoryType::Expense->value)->shuffle()->values();
        $taken = Category::query()->where('type', CategoryType::Expense->value)->pluck('name')->flip();

        return Collection::times($count, function () use ($samples, &$taken): Category {
            for ($round = 1; ; $round++) {
                foreach ($samples as $sample) {
                    $name = $round === 1 ? $sample['name'] : "{$sample['name']} {$round}";

                    if (! $taken->has($name)) {
                        $taken->put($name, true);
                        $category = Category::factory()->create([...$sample, 'name' => $name]);

                        foreach (range(1, fake()->numberBetween(2, 3)) as $ignored) {
                            CycleTypeFactory::createSample($category);
                        }

                        return $category;
                    }
                }
            }
        });
    }
}
