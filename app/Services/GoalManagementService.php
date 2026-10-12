<?php

namespace App\Services;

use App\Models\Goal;
use App\Repositories\Goal\GoalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GoalManagementService
{
    public function __construct(
        protected GoalRepositoryInterface $goalRepository,
        protected SubGoalManagementService $subGoalService,
    ) {}

    /**
     * List goals, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->goalRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single goal by ID.
     */
    public function find(int $id): ?Goal
    {
        return $this->goalRepository->find($id);
    }

    /**
     * Create a new goal, together with its sub goals when `sub_goals` is given.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Goal
    {
        $subGoals = Arr::pull($data, 'sub_goals');

        return DB::transaction(function () use ($data, $subGoals): Goal {
            $goal = $this->goalRepository->create($data);

            if ($subGoals) {
                $this->subGoalService->sync($goal, $subGoals);
            }

            return $goal;
        });
    }

    /**
     * Update an existing goal; when `sub_goals` is given the goal's sub goals are synced to it.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Goal $goal, array $data): Goal
    {
        $subGoals = Arr::pull($data, 'sub_goals');

        return DB::transaction(function () use ($goal, $data, $subGoals): Goal {
            $goal = $this->goalRepository->update($goal, $data);

            if ($subGoals !== null) {
                $this->subGoalService->sync($goal, $subGoals);
            }

            return $goal;
        });
    }

    /**
     * Delete a goal.
     */
    public function delete(Goal $goal): void
    {
        $this->goalRepository->delete($goal);
    }

    /**
     * Delete multiple goals by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->goalRepository->deleteMany($ids);
    }

    /**
     * Delete every goal matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->goalRepository->deleteMatching($search);
    }

    /**
     * Count goals matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->goalRepository->countMatching($search, $exceptId);
    }
}
