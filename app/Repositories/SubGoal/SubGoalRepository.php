<?php

namespace App\Repositories\SubGoal;

use App\Models\SubGoal;
use App\Repositories\Concerns\RunsTransactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubGoalRepository implements SubGoalRepositoryInterface
{
    use RunsTransactions;

    public function paginate(int $goalId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = SubGoal::query()->where('goal_id', $goalId);

        $this->applySearch($query, $search);

        $sortable = ['name', 'target_amount', 'saved_amount', 'desired_date', 'is_done', 'note', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->oldest();
        }

        return $query->orderBy('id', $direction)->paginate($perPage)->withQueryString();
    }

    public function getForGoal(int $goalId): Collection
    {
        return SubGoal::query()->where('goal_id', $goalId)->orderBy('id')->get();
    }

    public function summary(int $goalId): array
    {
        $query = SubGoal::query()->where('goal_id', $goalId);

        return [
            'total' => (clone $query)->count(),
            'done' => (clone $query)->where('is_done', true)->count(),
            'target_total' => number_format((float) (clone $query)->sum('target_amount'), 2, '.', ''),
            'saved_total' => number_format((float) $query->sum('saved_amount'), 2, '.', ''),
        ];
    }

    public function deleteMany(int $goalId, array $ids): void
    {
        SubGoal::query()->where('goal_id', $goalId)->whereIn('id', $ids)->delete();
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function ($inner) use ($search): void {
            $inner->where('name', 'like', "%{$search}%")
                ->orWhere('note', 'like', "%{$search}%");
        });
    }

    public function create(int $goalId, array $data): SubGoal
    {
        return SubGoal::query()->create([...$data, 'goal_id' => $goalId]);
    }

    public function update(SubGoal $subGoal, array $data): SubGoal
    {
        $subGoal->update($data);

        return $subGoal;
    }

    public function find(int $id): ?SubGoal
    {
        return SubGoal::query()->find($id);
    }

    public function incrementSavedAmount(int $id, float $difference): void
    {
        SubGoal::query()->whereKey($id)->increment('saved_amount', $difference);
    }
}
