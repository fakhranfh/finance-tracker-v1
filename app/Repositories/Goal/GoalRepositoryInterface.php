<?php

namespace App\Repositories\Goal;

use App\Models\Goal;
use Illuminate\Pagination\LengthAwarePaginator;

interface GoalRepositoryInterface
{
    /**
     * Find a goal by ID.
     */
    public function find(int $id): ?Goal;

    /**
     * Paginate goals, optionally filtered by search and sorted by column.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Delete goals by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every goal matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void;

    /**
     * Count goals matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int;

    /**
     * Create a new goal.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Goal;

    /**
     * Update an existing goal.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Goal $goal, array $data): Goal;

    /**
     * Delete a goal.
     */
    public function delete(Goal $goal): void;
}
