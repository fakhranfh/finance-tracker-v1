<?php

namespace App\Repositories\Category;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Find a category by ID.
     */
    public function find(int $id): ?Category;

    /**
     * Paginate categories, optionally filtered by search and sorted by column.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Delete categories by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every category matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void;

    /**
     * Count categories matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int;

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category;

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category;

    /**
     * Delete a category.
     */
    public function delete(Category $category): void;

    /**
     * The names of the given categories.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, string>
     */
    public function namesOf(array $ids): Collection;

    /**
     * Every category's ID, name and type, by name.
     *
     * @return Collection<int, Category>
     */
    public function allNamed(): Collection;
}
