<?php

namespace App\Repositories\GoalSaving;

use App\Models\Goal;
use App\Models\GoalSaving;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GoalSavingRepository implements GoalSavingRepositoryInterface
{
    public function paginate(Goal $goal, int $perPage, ?string $sort = null, string $direction = 'desc', ?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $subGoalId = null): LengthAwarePaginator
    {
        $query = GoalSaving::query()
            ->with('subGoal')
            ->whereBelongsTo($goal)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->when($subGoalId, fn ($q) => $q->where('sub_goal_id', $subGoalId));
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, ['amount', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        return $query->orderBy('id', $direction)->paginate($perPage)->withQueryString();
    }

    public function create(Goal $goal, string|float|int $amount, ?int $subGoalId = null): GoalSaving
    {
        return DB::transaction(function () use ($goal, $amount, $subGoalId): GoalSaving {
            $goal->increment('saved_amount', $amount);
            $goal->refresh();

            $saving = GoalSaving::query()->create(['goal_id' => $goal->id, 'sub_goal_id' => $subGoalId, 'amount' => $amount]);
            $this->shiftSubGoalSavedAmount($saving, (float) $amount);

            return $saving;
        });
    }

    public function update(Goal $goal, GoalSaving $saving, string|float|int $amount, ?int $subGoalId = null): GoalSaving
    {
        return DB::transaction(function () use ($goal, $saving, $amount, $subGoalId): GoalSaving {
            $this->shiftSavedAmount($goal, round((float) $amount - (float) $saving->amount, 2));
            $this->shiftSubGoalSavedAmount($saving, -(float) $saving->amount);
            $saving->update(['amount' => $amount, 'sub_goal_id' => $subGoalId]);
            $this->shiftSubGoalSavedAmount($saving, (float) $amount);

            return $saving;
        });
    }

    public function delete(Goal $goal, GoalSaving $saving): void
    {
        DB::transaction(function () use ($goal, $saving): void {
            $this->shiftSavedAmount($goal, -(float) $saving->amount);
            $this->shiftSubGoalSavedAmount($saving, -(float) $saving->amount);
            $saving->delete();
        });
    }

    public function deleteMany(Goal $goal, ?array $ids = null): void
    {
        DB::transaction(function () use ($goal, $ids): void {
            $query = GoalSaving::query()->whereBelongsTo($goal)->when($ids !== null, fn ($q) => $q->whereIn('id', $ids));

            $this->shiftSavedAmount($goal, -(float) $query->sum('amount'));
            (clone $query)->whereNotNull('sub_goal_id')->get()->each(fn (GoalSaving $saving) => $this->shiftSubGoalSavedAmount($saving, -(float) $saving->amount));
            $query->delete();
        });
    }

    /**
     * Move the saved amount of the saving's sub goal (if any) by a signed difference, so the sub goals keep adding up to the goal.
     */
    private function shiftSubGoalSavedAmount(GoalSaving $saving, float $difference): void
    {
        $saving->subGoal()->increment('saved_amount', $difference);
    }

    /**
     * Move the saved amount by a signed difference, never below zero.
     */
    private function shiftSavedAmount(Goal $goal, float $difference): void
    {
        $goal->increment('saved_amount', $difference);
        $goal->refresh();

        if ((float) $goal->saved_amount < 0) {
            $goal->update(['saved_amount' => 0]);
        }
    }
}
