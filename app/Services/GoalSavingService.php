<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\GoalSaving;
use App\Repositories\GoalSaving\GoalSavingRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class GoalSavingService
{
    public function __construct(
        protected GoalSavingRepositoryInterface $goalSavingRepository,
    ) {}

    /**
     * List a goal's saving history, optionally sorted (newest first by default) and limited
     * to the days between `$dateFrom` and `$dateTo` ("Y-m-d", in the user's timezone, both inclusive).
     */
    public function paginate(Goal $goal, int $perPage = 10, ?string $sort = null, string $direction = 'desc', ?string $dateFrom = null, ?string $dateTo = null, ?int $subGoalId = null): LengthAwarePaginator
    {
        $timezone = Auth::user()?->timezone ?: config('app.timezone');

        return $this->goalSavingRepository->paginate(
            $goal,
            $perPage,
            $sort,
            $direction,
            $dateFrom ? CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, $timezone)->startOfDay()->setTimezone(config('app.timezone')) : null,
            $dateTo ? CarbonImmutable::createFromFormat('!Y-m-d', $dateTo, $timezone)->endOfDay()->setTimezone(config('app.timezone')) : null,
            $subGoalId,
        );
    }

    /**
     * Add an amount to a goal's saved amount and keep it in the saving history.
     */
    public function addSaving(Goal $goal, string|float|int $amount, ?int $subGoalId = null): GoalSaving
    {
        return $this->goalSavingRepository->create($goal, $amount, $subGoalId);
    }

    /**
     * Change a saving's amount; the goal's saved amount follows.
     */
    public function updateSaving(Goal $goal, GoalSaving $saving, string|float|int $amount, ?int $subGoalId = null): GoalSaving
    {
        return $this->goalSavingRepository->update($goal, $saving, $amount, $subGoalId);
    }

    /**
     * Remove a saving; the goal's saved amount follows.
     */
    public function deleteSaving(Goal $goal, GoalSaving $saving): void
    {
        $this->goalSavingRepository->delete($goal, $saving);
    }

    /**
     * Remove several savings (every saving when `$ids` is null); the goal's saved amount follows.
     *
     * @param  array<int, int>|null  $ids
     */
    public function deleteSavings(Goal $goal, ?array $ids = null): void
    {
        $this->goalSavingRepository->deleteMany($goal, $ids);
    }
}
