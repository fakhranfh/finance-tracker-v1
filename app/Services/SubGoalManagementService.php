<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\SubGoal;
use App\Repositories\GoalSaving\GoalSavingRepositoryInterface;
use App\Repositories\SubGoal\SubGoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubGoalManagementService
{
    public function __construct(
        protected SubGoalRepositoryInterface $subGoalRepository,
        protected GoalSavingRepositoryInterface $goalSavingRepository,
    ) {}

    /**
     * List a goal's sub goals, paginated, optionally filtered and sorted.
     */
    public function paginate(Goal $goal, int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->subGoalRepository->paginate($goal->id, $perPage, $search, $sort, $direction);
    }

    /**
     * Every sub goal of the goal.
     *
     * @return Collection<int, SubGoal>
     */
    public function all(Goal $goal): Collection
    {
        return $this->subGoalRepository->getForGoal($goal->id);
    }

    /**
     * How many sub goals the goal has, how many are done and their combined target and saved amount.
     *
     * @return array{total: int, done: int, target_total: string, saved_total: string}
     */
    public function summary(Goal $goal): array
    {
        return $this->subGoalRepository->summary($goal->id);
    }

    /**
     * Update a sub goal; marking it done adds an entry to the saving history, marking it not done removes that entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SubGoal $subGoal, array $data): SubGoal
    {
        return $this->subGoalRepository->transaction(function () use ($subGoal, $data): SubGoal {
            $wasDone = $subGoal->is_done;
            $subGoal = $this->subGoalRepository->update($subGoal, $data);

            if (! $wasDone && $subGoal->is_done) {
                $this->goalSavingRepository->recordDone($subGoal->goal_id, $subGoal->id);
            } elseif ($wasDone && ! $subGoal->is_done) {
                $this->goalSavingRepository->deleteDone([$subGoal->id]);
            }

            return $subGoal;
        });
    }

    /**
     * Move a sub goal's saved amount by a signed difference. The sub goal is marked done once its saved amount
     * reaches its target and not done again when it falls below it.
     */
    public function shiftSavedAmount(int $id, float $difference): void
    {
        $this->subGoalRepository->transaction(function () use ($id, $difference): void {
            $this->subGoalRepository->incrementSavedAmount($id, $difference);

            $subGoal = $this->subGoalRepository->find($id);
            $reached = $subGoal !== null && (float) $subGoal->saved_amount >= (float) $subGoal->target_amount;

            if ($subGoal !== null && $reached !== $subGoal->is_done) {
                $this->update($subGoal, ['is_done' => $reached]);
            }
        });
    }

    /**
     * Replace the goal's sub goals with the given list, all or nothing; see sync().
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function replace(Goal $goal, array $items): void
    {
        $this->subGoalRepository->transaction(fn () => $this->sync($goal, $items));
    }

    /**
     * Make the goal's sub goals match the given list: items with an id are updated (their saved amount is
     * kept, it follows the savings), items without one are created and the sub goals left out are removed.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function sync(Goal $goal, array $items): void
    {
        $existing = $this->subGoalRepository->getForGoal($goal->id)->keyBy('id');
        $kept = [];

        foreach ($items as $item) {
            $fields = [
                'name' => $item['name'],
                'target_amount' => $item['target_amount'],
                'desired_date' => $item['desired_date'] ?? null,
                'note' => $item['note'] ?? null,
            ];
            $id = (int) ($item['id'] ?? 0);

            if ($existing->has($id)) {
                $this->subGoalRepository->update($existing[$id], $fields);
                $kept[] = $id;
            } else {
                $kept[] = $this->subGoalRepository->create($goal->id, [...$fields, 'saved_amount' => $item['saved_amount'] ?? 0])->id;
            }
        }

        $removed = $existing->keys()->diff($kept)->values()->all();

        if ($removed !== []) {
            $this->goalSavingRepository->deleteDone($removed);
            $this->subGoalRepository->deleteMany($goal->id, $removed);
        }
    }
}
