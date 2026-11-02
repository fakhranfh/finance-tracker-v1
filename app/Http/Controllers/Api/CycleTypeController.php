<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CycleType\BulkDeleteCycleTypeRequest;
use App\Http\Requests\CycleType\StoreCycleTypeRequest;
use App\Http\Requests\CycleType\UpdateCycleTypeRequest;
use App\Http\Resources\CycleTypeResource;
use App\Models\Category;
use App\Models\CycleType;
use App\Services\CycleCategoryService;
use App\Services\CycleTypeService;
use App\Traits\CycleType\DocumentsApi;
use App\Traits\CycleType\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CycleTypeController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected CycleTypeService $cycleTypeService,
        protected CycleCategoryService $cycleCategoryService,
    ) {}

    public function index(Request $request, Category $category): AnonymousResourceCollection
    {
        abort_unless($category->type === CategoryType::Expense, 404);

        $types = $this->cycleTypeService->paginateForCategory(
            $category,
            perPage: min(max($request->integer('per_page', 12), 1), 100),
            search: $request->string('search')->value() ?: null,
        );
        $category = $this->cycleCategoryService->detail($category);

        return CycleTypeResource::collection($types)->additional([
            'category' => ['id' => $category->id, 'name' => $category->name, 'monthly' => (int) $category->getAttribute('cycle_monthly')],
        ]);
    }

    public function generate(Request $request, Category $category): AnonymousResourceCollection
    {
        abort_unless($category->type === CategoryType::Expense, 404);

        $validated = $request->validate(['count' => ['required', 'integer', 'min:1', 'max:50']]);

        return CycleTypeResource::collection($this->generateSampleTypes($category, $validated['count']));
    }

    public function store(StoreCycleTypeRequest $request, Category $category): JsonResponse
    {
        abort_unless($category->type === CategoryType::Expense, 404);

        $type = $this->cycleTypeService->create($category, $request->validated());

        return (new CycleTypeResource($this->cycleTypeService->detail($type)))->response()->setStatusCode(201);
    }

    public function update(UpdateCycleTypeRequest $request, CycleType $type): CycleTypeResource
    {
        return new CycleTypeResource($this->cycleTypeService->update($type, $request->validated()));
    }

    public function destroy(CycleType $type): Response
    {
        $this->cycleTypeService->delete($type);

        return response()->noContent();
    }

    public function bulkDestroy(BulkDeleteCycleTypeRequest $request, Category $category): Response
    {
        $this->cycleTypeService->deleteMany($category, $request->validated('ids'));

        return response()->noContent();
    }

    public function show(CycleType $type): CycleTypeResource
    {
        return new CycleTypeResource($this->cycleTypeService->detail($type));
    }
}
