<?php

namespace App\Http\Controllers\Api;

use App\Enums\GoalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Goal\BulkDeleteGoalSavingRequest;
use App\Http\Requests\Goal\StoreGoalSavingRequest;
use App\Http\Requests\Goal\UpdateGoalSavingRequest;
use App\Http\Resources\GoalResource;
use App\Http\Resources\GoalSavingResource;
use App\Models\Goal;
use App\Models\GoalSaving;
use App\Services\GoalSavingService;
use App\Traits\GoalSaving\DocumentsApi;
use App\Traits\GoalSaving\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GoalSavingController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected GoalSavingService $goalSavingService,
    ) {}

    public function index(Request $request, Goal $goal): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sub_goal_id' => ['nullable', 'integer'],
        ]);

        return GoalSavingResource::collection($this->goalSavingService->paginate(
            $goal,
            min(max($request->integer('per_page', 10), 1), 100),
            $request->string('sort')->value() ?: null,
            $request->string('direction', 'desc')->value(),
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
            isset($filters['sub_goal_id']) ? (int) $filters['sub_goal_id'] : null,
        ));
    }

    public function update(UpdateGoalSavingRequest $request, Goal $goal, GoalSaving $saving): JsonResponse
    {
        abort_if($goal->status === GoalStatus::Reached, 403, __('goals.locked'));

        $saving = $this->goalSavingService->updateSaving($goal, $saving, $request->validated('amount'), $request->validated('sub_goal_id'));

        return (new GoalSavingResource($saving))
            ->additional(['goal' => (new GoalResource($goal))->resolve()])
            ->response();
    }

    public function destroy(Goal $goal, GoalSaving $saving): JsonResponse
    {
        abort_if($goal->status === GoalStatus::Reached, 403, __('goals.locked'));

        $this->goalSavingService->deleteSaving($goal, $saving);

        return response()->json(['goal' => (new GoalResource($goal))->resolve()]);
    }

    public function bulkDestroy(BulkDeleteGoalSavingRequest $request, Goal $goal): JsonResponse
    {
        abort_if($goal->status === GoalStatus::Reached, 403, __('goals.locked'));

        $this->goalSavingService->deleteSavings($goal, $request->boolean('all') ? null : $request->validated('ids'), $request->boolean('all') ? $request->validated('sub_goal_id') : null);

        return response()->json(['goal' => (new GoalResource($goal))->resolve()]);
    }

    public function generate(Request $request, Goal $goal): AnonymousResourceCollection
    {
        abort_if($goal->status === GoalStatus::Reached, 403, __('goals.locked'));

        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return GoalSavingResource::collection($this->generateSampleSavings($goal, $validated['count']))
            ->additional(['goal' => (new GoalResource($goal))->resolve()]);
    }

    public function store(StoreGoalSavingRequest $request, Goal $goal): JsonResponse
    {
        abort_if($goal->status === GoalStatus::Reached, 403, __('goals.locked'));

        $saving = $this->goalSavingService->addSaving($goal, $request->validated('amount'), $request->validated('sub_goal_id'));

        return (new GoalSavingResource($saving))
            ->additional(['goal' => (new GoalResource($goal))->resolve()])
            ->response()
            ->setStatusCode(201);
    }
}
