<?php

namespace App\Repositories\CycleType;

use App\Models\CycleType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CycleTypeRepositoryInterface
{
    /**
     * Every item type of the user, with its purchases.
     *
     * @return Collection<int, CycleType>
     */
    public function allWithEntries(): Collection;

    /**
     * The item types of a category, by name, with their purchases.
     *
     * @return Collection<int, CycleType>
     */
    public function forCategory(int $categoryId): Collection;

    /**
     * Paginate the item types of a category, by name, optionally filtered by search, with their purchases.
     */
    public function paginateForCategory(int $categoryId, int $perPage, ?string $search = null): LengthAwarePaginator;

    /**
     * Create an item type in the category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(int $categoryId, array $data): CycleType;

    /**
     * Load the item type's category (id and name) and its purchases, oldest first.
     */
    public function loadDetail(CycleType $type): CycleType;
}
