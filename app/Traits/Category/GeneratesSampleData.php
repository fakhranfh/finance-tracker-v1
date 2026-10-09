<?php

namespace App\Traits\Category;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the categories list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample categories from the realistic presets in config/category.php, alternating
     * income and expense. A preset already in use is numbered ("Gaji 2") rather than duplicated.
     *
     * @return Collection<int, Category>
     */
    protected function generateSampleCategories(int $count = 10): Collection
    {
        $samples = collect(config('category.samples'))->shuffle()->groupBy('type');
        $taken = Category::query()->get(['name', 'type'])
            ->mapWithKeys(fn (Category $category) => ["{$category->type->value}|{$category->name}" => true])
            ->all();

        return Collection::times($count, function (int $i) use ($samples, &$taken): Category {
            $type = $i % 2 ? CategoryType::Expense : CategoryType::Income;

            for ($round = 1; ; $round++) {
                foreach ($samples[$type->value] as $sample) {
                    $name = $round === 1 ? $sample['name'] : "{$sample['name']} {$round}";

                    if (! isset($taken["{$type->value}|{$name}"])) {
                        $taken["{$type->value}|{$name}"] = true;

                        return Category::factory()->create([...$sample, 'name' => $name]);
                    }
                }
            }
        });
    }
}
