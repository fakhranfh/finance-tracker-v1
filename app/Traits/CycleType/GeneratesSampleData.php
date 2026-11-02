<?php

namespace App\Traits\CycleType;

use App\Models\Category;
use App\Models\CycleType;
use Database\Factories\CycleTypeFactory;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the item names list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample item names (Sabun Mandi, Serum, ...) in the category, each with a purchase history.
     *
     * @return Collection<int, CycleType>
     */
    protected function generateSampleTypes(Category $category, int $count = 3): Collection
    {
        return Collection::times($count, fn () => CycleTypeFactory::createSample($category));
    }
}
