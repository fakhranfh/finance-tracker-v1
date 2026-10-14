<?php

namespace App\Repositories\SubGoal;

use App\Models\SubGoal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubGoalRepositoryInterface
{
    /**
     * Paginate a goal's sub goals, optionally filtered by search and sorted by column.
     */
    public function paginate(int $goalId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Every sub goal of the goal.
     *
     * @return Collection<int, SubGoal>
     */
    public function getForGoal(int $goalId): Collection;

    /**
     * Count a goal's sub goals, how many are done and their combined target and saved amount.
     *
     * @return array{total: int, done: int, target_total: string, saved_total: string}
     */
    public function summary(int $goalId): array;

    /**
     * Delete the given sub goals of the goal.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(int $goalId, array $ids): void;

    /**
     * Create a new sub goal for the goal.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(int $goalId, array $data): SubGoal;

    /**
     * Update an existing sub goal.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SubGoal $subGoal, array $data): SubGoal;

    /**
     * Move the saved amount of a sub goal by a signed difference.
     */
    public function incrementSavedAmount(int $id, float $difference): void;

    /**
     * Find a sub goal by ID.
     */
    public function find(int $id): ?SubGoal;

    /**
     * Run the callback inside a database transaction and return its result.
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    public function transaction(\Closure $callback): mixed;
}
