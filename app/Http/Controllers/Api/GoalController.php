<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Goal\BulkDeleteGoalRequest;
use App\Http\Requests\Goal\StoreGoalRequest;
use App\Http\Requests\Goal\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Services\GoalManagementService;
use App\Traits\Goal\DocumentsApi;
use App\Traits\Goal\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GoalController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected GoalManagementService $goalManagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;

        return GoalResource::collection($this->goalManagementService->paginate(
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'deletable_total' => $this->goalManagementService->countMatching($search),
        ]);
    }

    public function bulkDestroy(BulkDeleteGoalRequest $request): Response
    {
        if ($request->boolean('all')) {
            $this->goalManagementService->deleteMatching($request->validated('search'));
        } else {
            $this->goalManagementService->deleteMany($request->validated('ids'));
        }

        return response()->noContent();
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return GoalResource::collection($this->generateSampleGoals($validated['count']));
    }

    public function show(Goal $goal): GoalResource
    {
        return new GoalResource($goal);
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        $goal = $this->goalManagementService->create($request->validated());

        return (new GoalResource($goal))->response()->setStatusCode(201);
    }

    public function update(UpdateGoalRequest $request, Goal $goal): GoalResource
    {
        return new GoalResource($this->goalManagementService->update($goal, $request->validated()));
    }

    public function destroy(Goal $goal): Response
    {
        $this->goalManagementService->delete($goal);

        return response()->noContent();
    }
}
