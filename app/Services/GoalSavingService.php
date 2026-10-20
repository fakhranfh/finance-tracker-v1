<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\GoalSaving;
use App\Repositories\Goal\GoalRepositoryInterface;
use App\Repositories\GoalSaving\GoalSavingRepositoryInterface;
use App\Repositories\SubGoal\SubGoalRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class GoalSavingService
{
    public function __construct(
        protected GoalRepositoryInterface $goalRepository,
        protected GoalSavingRepositoryInterface $goalSavingRepository,
        protected SubGoalRepositoryInterface $subGoalRepository,
        protected SubGoalManagementService $subGoalService,
    ) {}

    /**
     * List a goal's saving history, optionally sorted (newest first by default) and limited
     * to the days between `$dateFrom` and `$dateTo` ("Y-m-d", in the user's timezone, both inclusive).
     */
    public function paginate(Goal $goal, int $perPage = 10, ?string $sort = null, string $direction = 'desc', ?string $dateFrom = null, ?string $dateTo = null, ?int $subGoalId = null): LengthAwarePaginator
    {
        $timezone = Auth::user()?->timezone ?: config('app.timezone');

        return $this->goalSavingRepository->paginate(
            $goal->id,
            $perPage,
            $sort,
            $direction,
            $dateFrom ? CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, $timezone)->startOfDay()->setTimezone(config('app.timezone')) : null,
            $dateTo ? CarbonImmutable::createFromFormat('!Y-m-d', $dateTo, $timezone)->endOfDay()->setTimezone(config('app.timezone')) : null,
            $subGoalId,
        );
    }

    /**
     * Add an amount to a goal's saved amount and keep it in the saving history; the sub goal it is for gets it too.
     */
    public function addSaving(Goal $goal, string|float|int $amount, ?int $subGoalId = null): GoalSaving
    {
        return $this->goalSavingRepository->transaction(function () use ($goal, $amount, $subGoalId): GoalSaving {
            $this->goalRepository->shiftSavedAmount($goal, (float) $amount);
            $saving = $this->goalSavingRepository->create($goal->id, $amount, $subGoalId);

            $this->shiftSubGoal($subGoalId, (int) $amount);

            return $saving;
        });
    }

    /**
     * Change a saving's amount; the goal's saved amount and the sub goals' follow.
     */
    public function updateSaving(Goal $goal, GoalSaving $saving, string|float|int $amount, ?int $subGoalId = null): GoalSaving
    {
        return $this->goalSavingRepository->transaction(function () use ($goal, $saving, $amount, $subGoalId): GoalSaving {
            $this->goalRepository->shiftSavedAmount($goal, (int) round((float) $amount - (float) $saving->amount, 0));
            $this->shiftSubGoal($saving->sub_goal_id, -(int) $saving->amount);
            $saving = $this->goalSavingRepository->update($saving, $amount, $subGoalId);
            $this->shiftSubGoal($subGoalId, (int) $amount);

            return $saving;
        });
    }

    /**
     * Remove a saving; the goal's saved amount and its sub goal's follow, and removing a "done" entry marks its sub goal as not done.
     */
    public function deleteSaving(Goal $goal, GoalSaving $saving): void
    {
        $this->goalSavingRepository->transaction(function () use ($goal, $saving): void {
            $this->goalRepository->shiftSavedAmount($goal, -(float) $saving->amount);
            $this->releaseSubGoals(collect([$saving]));
            $this->goalSavingRepository->delete($saving);
        });
    }

    /**
     * Remove several savings (every saving when `$ids` is null, limited to one sub goal's when `$subGoalId` is given); the goal's saved amount and the sub goals' follow.
     *
     * @param  array<int, int>|null  $ids
     */
    public function deleteSavings(Goal $goal, ?array $ids = null, ?int $subGoalId = null): void
    {
        $this->goalSavingRepository->transaction(function () use ($goal, $ids, $subGoalId): void {
            $savings = $this->goalSavingRepository->getForGoal($goal->id, $ids, $subGoalId);

            $this->goalRepository->shiftSavedAmount($goal, -(float) $savings->sum('amount'));
            $this->releaseSubGoals($savings);
            $this->goalSavingRepository->deleteMany($goal->id, $ids, $subGoalId);
        });
    }

    private function shiftSubGoal(?int $subGoalId, int $difference): void
    {
        if ($subGoalId !== null && $difference !== 0) {
            $this->subGoalService->shiftSavedAmount($subGoalId, $difference);
        }
    }

    /**
     * Take the savings about to be removed off their sub goals: subtract their amounts and mark the sub goals of "done" entries as not done, which puts back their saved amount from before.
     *
     * @param  Collection<int, GoalSaving>  $savings
     */
    private function releaseSubGoals(Collection $savings): void
    {
        $withSubGoal = $savings->whereNotNull('sub_goal_id');

        $withSubGoal->groupBy('sub_goal_id')->each(fn (Collection $group, int $subGoalId) => $this->shiftSubGoal($subGoalId, -(int) $group->sum('amount')));

        $doneIds = $withSubGoal->where('type', GoalSaving::TYPE_DONE)->pluck('sub_goal_id')->unique()->values()->all();

        foreach ($doneIds as $doneId) {
            $subGoal = $this->subGoalRepository->find($doneId);

            if ($subGoal !== null) {
                $this->subGoalService->update($subGoal, ['is_done' => false]);
            }
        }
    }
}
