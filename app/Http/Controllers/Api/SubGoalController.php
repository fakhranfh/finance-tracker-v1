<?php

namespace App\Http\Controllers\Api;

use App\Enums\GoalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubGoal\SyncSubGoalsRequest;
use App\Http\Requests\SubGoal\UpdateSubGoalRequest;
use App\Http\Resources\SubGoalResource;
use App\Models\Goal;
use App\Models\SubGoal;
use App\Services\SubGoalManagementService;
use App\Traits\SubGoal\DocumentsApi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Sub goals are created, edited and removed as a set, together with their goal (see the goal endpoints)
 * or on their own through `sync`; this controller also lists them and marks them done.
 */
class SubGoalController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected SubGoalManagementService $subGoalManagementService,
    ) {}

    public function index(Request $request, Goal $goal): AnonymousResourceCollection
    {
        return SubGoalResource::collection($this->subGoalManagementService->paginate(
            $goal,
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $request->string('search')->value() ?: null,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'summary' => $this->subGoalManagementService->summary($goal),
        ]);
    }

    public function sync(SyncSubGoalsRequest $request, Goal $goal): AnonymousResourceCollection
    {
        abort_if($goal->status === GoalStatus::Reached, 403, __('goals.locked'));

        $this->subGoalManagementService->replace($goal, $request->validated('sub_goals'));

        return SubGoalResource::collection($this->subGoalManagementService->all($goal))->additional([
            'summary' => $this->subGoalManagementService->summary($goal),
        ]);
    }

    public function show(Goal $goal, SubGoal $subGoal): SubGoalResource
    {
        return new SubGoalResource($subGoal);
    }

    public function update(UpdateSubGoalRequest $request, Goal $goal, SubGoal $subGoal): SubGoalResource
    {
        abort_if($goal->status === GoalStatus::Reached, 403, __('goals.locked'));

        return new SubGoalResource($this->subGoalManagementService->update($subGoal, $request->validated()));
    }
}
