<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryManagementService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository,
    ) {}

    /**
     * List categories, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single category by ID.
     */
    public function find(int $id): ?Category
    {
        return $this->categoryRepository->find($id);
    }

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return $this->categoryRepository->create($data);
    }

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        return $this->categoryRepository->update($category, $data);
    }

    /**
     * Delete a category.
     */
    public function delete(Category $category): void
    {
        $this->categoryRepository->delete($category);
    }

    /**
     * Delete multiple categories by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->categoryRepository->deleteMany($ids);
    }

    /**
     * Delete every category matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->categoryRepository->deleteMatching($search);
    }

    /**
     * Count categories matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->categoryRepository->countMatching($search, $exceptId);
    }
}
