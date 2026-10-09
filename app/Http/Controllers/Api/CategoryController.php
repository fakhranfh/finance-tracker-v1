<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\BulkDeleteCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryManagementService;
use App\Traits\Category\DocumentsApi;
use App\Traits\Category\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected CategoryManagementService $categoryManagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;

        return CategoryResource::collection($this->categoryManagementService->paginate(
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'deletable_total' => $this->categoryManagementService->countMatching($search),
        ]);
    }

    public function bulkDestroy(BulkDeleteCategoryRequest $request): Response
    {
        if ($request->boolean('all')) {
            $this->categoryManagementService->deleteMatching($request->validated('search'));
        } else {
            $this->categoryManagementService->deleteMany($request->validated('ids'));
        }

        return response()->noContent();
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return CategoryResource::collection($this->generateSampleCategories($validated['count']));
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryManagementService->create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        return new CategoryResource($this->categoryManagementService->update($category, $request->validated()));
    }

    public function destroy(Category $category): Response
    {
        $this->categoryManagementService->delete($category);

        return response()->noContent();
    }
}
