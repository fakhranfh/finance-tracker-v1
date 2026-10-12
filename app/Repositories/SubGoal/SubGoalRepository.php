<?php

namespace App\Repositories\SubGoal;

use App\Models\Goal;
use App\Models\SubGoal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubGoalRepository implements SubGoalRepositoryInterface
{
    public function paginate(Goal $goal, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = SubGoal::query()->whereBelongsTo($goal);

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

    public function getForGoal(Goal $goal): Collection
    {
        return SubGoal::query()->whereBelongsTo($goal)->orderBy('id')->get();
    }

    public function summary(Goal $goal): array
    {
        $query = SubGoal::query()->whereBelongsTo($goal);

        return [
            'total' => (clone $query)->count(),
            'done' => (clone $query)->where('is_done', true)->count(),
            'target_total' => number_format((float) (clone $query)->sum('target_amount'), 2, '.', ''),
            'saved_total' => number_format((float) $query->sum('saved_amount'), 2, '.', ''),
        ];
    }

    public function deleteMany(Goal $goal, array $ids): void
    {
        SubGoal::query()->whereBelongsTo($goal)->whereIn('id', $ids)->delete();
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

    public function create(Goal $goal, array $data): SubGoal
    {
        return SubGoal::query()->create([...$data, 'goal_id' => $goal->id]);
    }

    public function update(SubGoal $subGoal, array $data): SubGoal
    {
        $subGoal->update($data);

        return $subGoal;
    }
}
