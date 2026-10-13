<?php

namespace App\Repositories\GoalSaving;

use App\Models\GoalSaving;
use App\Repositories\Concerns\RunsTransactions;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class GoalSavingRepository implements GoalSavingRepositoryInterface
{
    use RunsTransactions;

    public function paginate(int $goalId, int $perPage, ?string $sort = null, string $direction = 'desc', ?CarbonInterface $from = null, ?CarbonInterface $to = null, ?int $subGoalId = null): LengthAwarePaginator
    {
        $query = GoalSaving::query()
            ->with('subGoal')
            ->where('goal_id', $goalId)
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

    public function create(int $goalId, string|float|int $amount, ?int $subGoalId = null): GoalSaving
    {
        return GoalSaving::query()->create(['goal_id' => $goalId, 'sub_goal_id' => $subGoalId, 'amount' => $amount]);
    }

    public function update(GoalSaving $saving, string|float|int $amount, ?int $subGoalId = null): GoalSaving
    {
        $saving->update(['amount' => $amount, 'sub_goal_id' => $subGoalId]);

        return $saving;
    }

    public function delete(GoalSaving $saving): void
    {
        $saving->delete();
    }

    public function deleteMany(int $goalId, ?array $ids = null): void
    {
        GoalSaving::query()->where('goal_id', $goalId)->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->delete();
    }

    public function getForGoal(int $goalId, ?array $ids = null): Collection
    {
        return GoalSaving::query()->where('goal_id', $goalId)->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->get();
    }

    public function recordDone(int $goalId, int $subGoalId): GoalSaving
    {
        return GoalSaving::query()->create(['goal_id' => $goalId, 'sub_goal_id' => $subGoalId, 'type' => GoalSaving::TYPE_DONE, 'amount' => 0]);
    }

    public function deleteDone(array $subGoalIds): void
    {
        GoalSaving::query()->whereIn('sub_goal_id', $subGoalIds)->where('type', GoalSaving::TYPE_DONE)->delete();
    }
}
