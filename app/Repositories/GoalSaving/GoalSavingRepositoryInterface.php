<?php

namespace App\Repositories\GoalSaving;

use App\Models\Goal;
use App\Models\GoalSaving;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface GoalSavingRepositoryInterface
{
    /**
     * Paginate a goal's saving history, optionally sorted (newest first by default) and limited to a time range.
     */
    public function paginate(Goal $goal, int $perPage, ?string $sort = null, string $direction = 'desc', ?CarbonInterface $from = null, ?CarbonInterface $to = null): LengthAwarePaginator;

    /**
     * Record an amount in the goal's saving history and add it to the saved amount.
     */
    public function create(Goal $goal, string|float|int $amount): GoalSaving;

    /**
     * Change a saving's amount and move the goal's saved amount by the difference.
     */
    public function update(Goal $goal, GoalSaving $saving, string|float|int $amount): GoalSaving;

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
