<?php

namespace App\Repositories\GoalSaving;

use App\Models\Goal;
use App\Models\GoalSaving;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface GoalSavingRepositoryInterface
{
    /**
     * Paginate a goal's saving history, optionally sorted (newest first by default) and limited to a time range and to the savings of one sub goal.
     */
    public function paginate(int $goalId, int $perPage, ?string $sort = null, string $direction = 'desc', ?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $subGoalId = null): LengthAwarePaginator;

    /**
     * Record an amount (optionally for one of the goal's sub goals) in the saving history and add it to the saved amount.
     */
    public function create(int $goalId, string|float|int $amount, ?int $subGoalId = null): GoalSaving;

    /**
     * Change a saving's amount and sub goal and move the goal's saved amount by the difference.
     */
    public function update(GoalSaving $saving, string|float|int $amount, ?int $subGoalId = null): GoalSaving;

    /**
     * Remove a saving and subtract it from the goal's saved amount.
     */
    public function delete(GoalSaving $saving): void;

    /**
     * Remove the given savings of the goal (every saving when null) and subtract them from its saved amount.
     *
     * @param  array<int, int>|null  $ids
     */
    public function deleteMany(int $goalId, ?array $ids = null, ?int $subGoalId = null): void;

    /**
     * The goal's savings, limited to the given ids when not null.
     *
     * @param  array<int, int>|null  $ids
     * @return Collection<int, GoalSaving>
     */
    public function getForGoal(int $goalId, ?array $ids = null, ?int $subGoalId = null): Collection;

    /**
     * Note in the saving history (amount 0) that a sub goal of the goal was marked as done.
     */
    public function recordDone(int $goalId, int $subGoalId): GoalSaving;

    /**
     * Remove the "marked as done" history entries of the given sub goals.
     *
     * @param  array<int, int>  $subGoalIds
     */
    public function deleteDone(array $subGoalIds): void;

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
