<?php

namespace App\Repositories\GoalSaving;

use App\Models\Goal;
use App\Models\GoalSaving;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GoalSavingRepository implements GoalSavingRepositoryInterface
{
    public function paginate(Goal $goal, int $perPage, ?string $sort = null, string $direction = 'desc', ?CarbonInterface $from = null, ?CarbonInterface $to = null): LengthAwarePaginator
    {
        $query = GoalSaving::query()
            ->whereBelongsTo($goal)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, ['amount', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        return $query->orderBy('id', $direction)->paginate($perPage)->withQueryString();
    }

    public function create(Goal $goal, string|float|int $amount): GoalSaving
    {
        return DB::transaction(function () use ($goal, $amount): GoalSaving {
            $goal->increment('saved_amount', $amount);
            $goal->refresh();

            return GoalSaving::query()->create(['goal_id' => $goal->id, 'amount' => $amount]);
        });
    }

    public function update(Goal $goal, GoalSaving $saving, string|float|int $amount): GoalSaving
    {
        return DB::transaction(function () use ($goal, $saving, $amount): GoalSaving {
            $this->shiftSavedAmount($goal, round((float) $amount - (float) $saving->amount, 2));
            $saving->update(['amount' => $amount]);

            return $saving;
        });
    }

    public function delete(Goal $goal, GoalSaving $saving): void
    {
        DB::transaction(function () use ($goal, $saving): void {
            $this->shiftSavedAmount($goal, -(float) $saving->amount);
            $saving->delete();
        });
    }

    public function deleteMany(Goal $goal, ?array $ids = null): void
    {
        DB::transaction(function () use ($goal, $ids): void {
            $query = GoalSaving::query()->whereBelongsTo($goal)->when($ids !== null, fn ($q) => $q->whereIn('id', $ids));

            $this->shiftSavedAmount($goal, -(float) $query->sum('amount'));
            $query->delete();
        });
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
