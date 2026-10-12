<?php

namespace App\Repositories\GoalSaving;

use App\Models\Goal;
use App\Models\GoalSaving;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface GoalSavingRepositoryInterface
{
    /**
     * Paginate a goal's saving history, optionally sorted (newest first by default) and limited to a time range and to the savings of one sub goal.
     */
    public function paginate(Goal $goal, int $perPage, ?string $sort = null, string $direction = 'desc', ?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $subGoalId = null): LengthAwarePaginator;

    /**
     * Record an amount (optionally for one of the goal's sub goals) in the saving history and add it to the saved amount.
     */
    public function create(Goal $goal, string|float|int $amount, ?int $subGoalId = null): GoalSaving;

    /**
     * Change a saving's amount and sub goal and move the goal's saved amount by the difference.
     */
    public function update(Goal $goal, GoalSaving $saving, string|float|int $amount, ?int $subGoalId = null): GoalSaving;

    /**
     * Remove a saving and subtract it from the goal's saved amount.
     */
    public function delete(Goal $goal, GoalSaving $saving): void;

    /**
     * Remove the given savings of the goal (every saving when null) and subtract them from its saved amount.
     *
     * @param  array<int, int>|null  $ids
     */
    public function deleteMany(Goal $goal, ?array $ids = null): void;
}
