<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\SubGoal;
use App\Repositories\Goal\GoalRepositoryInterface;
use App\Repositories\GoalSaving\GoalSavingRepositoryInterface;
use App\Repositories\SubGoal\SubGoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubGoalManagementService
{
    public function __construct(
        protected GoalRepositoryInterface $goalRepository,
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
     * Update a sub goal by hand. Marking it done sets its saved amount to the target (remembering the previous
     * value, the goal's saved amount follows) and marking it not done puts that previous value back.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SubGoal $subGoal, array $data): SubGoal
    {
        return $this->subGoalRepository->transaction(function () use ($subGoal, $data): SubGoal {
            $becomesDone = ($data['is_done'] ?? $subGoal->is_done) && ! $subGoal->is_done;
            $becomesNotDone = array_key_exists('is_done', $data) && ! $data['is_done'] && $subGoal->is_done;
            $saved = (float) $subGoal->saved_amount;
            $editedSaved = isset($data['saved_amount']) ? (float) $data['saved_amount'] : null;
            unset($data['saved_amount']);

            if ($becomesDone) {
                $data['saved_amount_before_done'] = $subGoal->saved_amount;
                $data['saved_amount'] = $subGoal->target_amount;
            } elseif ($becomesNotDone && $subGoal->saved_amount_before_done !== null) {
                $data['saved_amount'] = $subGoal->saved_amount_before_done;
                $data['saved_amount_before_done'] = null;
            }

            $subGoal = $this->setDone($subGoal, $data);
            $difference = round((float) $subGoal->saved_amount - $saved, 2);

            if ($difference !== 0.0) {
                $this->goalRepository->shiftSavedAmount($subGoal->goal, $difference);
            } elseif ($editedSaved !== null && ! $becomesDone && ! $becomesNotDone) {
                $subGoal = $this->editSavedAmount($subGoal, round($editedSaved - $saved, 2));
            }

            return $subGoal;
        });
    }

    /**
     * Move a sub goal's saved amount by hand; the goal's saved amount follows and the done status with it.
     */
    private function editSavedAmount(SubGoal $subGoal, float $difference): SubGoal
    {
        if ($difference !== 0.0) {
            $this->shiftSavedAmount($subGoal->id, $difference);
            $this->goalRepository->shiftSavedAmount($subGoal->goal, $difference);
            $subGoal->refresh();
        }

        return $subGoal;
    }

    /**
     * Update a sub goal; marking it done adds an entry to the saving history, marking it not done removes that entry.
     *
     * @param  array<string, mixed>  $data
     */
    private function setDone(SubGoal $subGoal, array $data): SubGoal
    {
        $wasDone = $subGoal->is_done;
        $subGoal = $this->subGoalRepository->update($subGoal, $data);

        if (! $wasDone && $subGoal->is_done) {
            $this->goalSavingRepository->recordDone($subGoal->goal_id, $subGoal->id);
        } elseif ($wasDone && ! $subGoal->is_done) {
            $this->goalSavingRepository->deleteDone([$subGoal->id]);
        }

        return $subGoal;
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
                $this->setDone($subGoal, ['is_done' => $reached]);
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
        $this->subGoalRepository->transaction(function () use ($goal, $items): void {
            $before = (float) $this->subGoalRepository->getForGoal($goal->id)->sum('saved_amount');

            $this->sync($goal, $items);

            $difference = round((float) $this->subGoalRepository->getForGoal($goal->id)->sum('saved_amount') - $before, 2);

            if ($difference !== 0.0) {
                $this->goalRepository->shiftSavedAmount($goal, $difference);
            }
        });
    }

    /**
     * Make the goal's sub goals match the given list: items with an id are updated (their saved amount moves to the one
     * given, if any), items without one are created and the sub goals left out are removed.
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
                $difference = isset($item['saved_amount']) ? round((float) $item['saved_amount'] - (float) $existing[$id]->saved_amount, 2) : 0.0;

                $this->subGoalRepository->update($existing[$id], $fields);
                $kept[] = $id;

                if ($difference !== 0.0) {
                    $this->shiftSavedAmount($id, $difference);
                }
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
