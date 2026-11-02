<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Resources\CycleCategoryResource;
use App\Models\Category;
use App\Services\CycleCategoryService;
use App\Traits\CycleCategory\DocumentsApi;
use App\Traits\CycleCategory\GeneratesSampleData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The first level of Usage Cycle. Categories themselves are created and edited through the category endpoints.
 */
class CycleCategoryController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected CycleCategoryService $cycleCategoryService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $overview = $this->cycleCategoryService->overview(
            perPage: min(max($request->integer('per_page', 12), 1), 100),
            search: $request->string('search')->value() ?: null,
        );

        return CycleCategoryResource::collection($overview['categories'])->additional([
            'summary' => ['monthly_total' => $overview['monthly_total']],
        ]);
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate(['count' => ['required', 'integer', 'min:1', 'max:50']]);

        return CycleCategoryResource::collection($this->generateSampleCategories($validated['count']));
    }

    public function show(Category $category): CycleCategoryResource
    {
        abort_unless($category->type === CategoryType::Expense, 404);

        return new CycleCategoryResource($this->cycleCategoryService->detail($category));
    }
}
