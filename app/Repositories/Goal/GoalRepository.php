<?php

namespace App\Repositories\Goal;

use App\Models\Goal;
use App\Repositories\Concerns\RunsTransactions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GoalRepository implements GoalRepositoryInterface
{
    use RunsTransactions;

    public function find(int $id): ?Goal
    {
        return Goal::query()->find($id);
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = Goal::query();

        $this->applySearch($query, $search);

        $sortable = ['name', 'target_amount', 'saved_amount', 'desired_date', 'status', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function deleteMany(array $ids): void
    {
        Goal::query()->whereIn('id', $ids)->delete();
    }

    public function deleteMatching(?string $search = null): void
    {
        $query = Goal::query();

        $this->applySearch($query, $search);

        $query->delete();
    }

    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        $query = Goal::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);

        return $query->count();
    }

    public function totals(): array
    {
        return [
            'target_total' => (float) Goal::query()->sum('target_amount'),
            'saved_total' => (float) Goal::query()->sum('saved_amount'),
        ];
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

    public function create(array $data): Goal
    {
        return Goal::query()->create($data);
    }

    public function update(Goal $goal, array $data): Goal
    {
        $goal->update($data);

        return $goal;
    }

    public function delete(Goal $goal): void
    {
        $goal->delete();
    }

    public function shiftSavedAmount(Goal $goal, float $difference): void
    {
        $goal->increment('saved_amount', $difference);
        $goal->refresh();

        if ((float) $goal->saved_amount < 0) {
            $goal->update(['saved_amount' => 0]);
        }
    }
}
