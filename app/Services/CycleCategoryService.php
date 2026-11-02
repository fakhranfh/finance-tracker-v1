<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CycleType;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\CycleType\CycleTypeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The first level of Usage Cycle: the expense categories, each with the monthly cost of the item types inside it.
 */
class CycleCategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository,
        protected CycleTypeRepositoryInterface $cycleTypeRepository,
        protected CycleUsageCalculator $calculator,
    ) {}

    /**
     * A page of the expense categories, each with its monthly cost (`cycle_monthly`), and the monthly total of
     * all of them (not just this page or the ones matching the search).
     *
     * @return array{categories: LengthAwarePaginator<int, Category>, monthly_total: int}
     */
    public function overview(int $perPage = 12, ?string $search = null): array
    {
        $monthlyByCategory = $this->cycleTypeRepository->allWithEntries()
            ->groupBy('category_id')
            ->map(fn ($types) => (int) $types->sum(fn (CycleType $type) => $this->calculator->forEntries($type->entries)['monthly'] ?? 0));

        $categories = $this->categoryRepository->paginateExpense($perPage, $search);

        foreach ($categories->items() as $category) {
            $category->setAttribute('cycle_monthly', (int) ($monthlyByCategory[$category->id] ?? 0));
        }

        return ['categories' => $categories, 'monthly_total' => (int) $monthlyByCategory->sum()];
    }

    /**
     * A category with its own monthly cost (`cycle_monthly`): the sum over all of its item types.
     */
    public function detail(Category $category): Category
    {
        $monthly = $this->cycleTypeRepository->forCategory($category->id)
            ->sum(fn (CycleType $type) => $this->calculator->forEntries($type->entries)['monthly'] ?? 0);

        $category->setAttribute('cycle_monthly', (int) $monthly);

        return $category;
    }
}
