<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CycleType;
use App\Repositories\CycleType\CycleTypeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CycleTypeService
{
    public function __construct(
        protected CycleTypeRepositoryInterface $cycleTypeRepository,
        protected CycleUsageCalculator $calculator,
    ) {}

    /**
     * Create an item type in the category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Category $category, array $data): CycleType
    {
        return $this->cycleTypeRepository->create($category->id, [...$data, 'icon' => $data['icon'] ?? config('cycle.default_icon')]);
    }

    /**
     * A page of the category's item types, each carrying `avg_days` and `monthly`.
     *
     * @return LengthAwarePaginator<int, CycleType>
     */
    public function paginateForCategory(Category $category, int $perPage = 12, ?string $search = null): LengthAwarePaginator
    {
        $types = $this->cycleTypeRepository->paginateForCategory($category->id, $perPage, $search);

        foreach ($types->items() as $type) {
            $this->describe($type);
        }

        return $types;
    }

    /**
     * An item type with its category, `avg_days` and `monthly`.
     */
    public function detail(CycleType $type): CycleType
    {
        return $this->describe($this->cycleTypeRepository->loadDetail($type));
    }

    /**
     * Put the numbers worked out from the item type's purchases on it and drop the purchases themselves.
     */
    private function describe(CycleType $type): CycleType
    {
        $usage = $this->calculator->forEntries($type->entries);

        $type->setAttribute('avg_days', $usage['avg_days']);
        $type->setAttribute('monthly', $usage['monthly']);
        $type->unsetRelation('entries');

        return $type;
    }
}
