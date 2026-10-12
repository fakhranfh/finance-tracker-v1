<?php

namespace App\Services;

use App\Models\Goal;
use App\Repositories\Goal\GoalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class GoalManagementService
{
    public function __construct(
        protected GoalRepositoryInterface $goalRepository,
    ) {}

    /**
     * List goals, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->goalRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single goal by ID.
     */
    public function find(int $id): ?Goal
    {
        return $this->goalRepository->find($id);
    }

    /**
     * Create a new goal.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Goal
    {
        return $this->goalRepository->create($data);
    }

    /**
     * Update an existing goal.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Goal $goal, array $data): Goal
    {
        return $this->goalRepository->update($goal, $data);
    }

    /**
     * Delete a goal.
     */
    public function delete(Goal $goal): void
    {
        $this->goalRepository->delete($goal);
    }

    /**
     * Delete multiple goals by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->goalRepository->deleteMany($ids);
    }

    /**
     * Delete every goal matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->goalRepository->deleteMatching($search);
    }

    /**
     * Count goals matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->goalRepository->countMatching($search, $exceptId);
    }
}
