<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubDebt\BulkDeleteSubDebtRequest;
use App\Http\Requests\SubDebt\StoreSubDebtRequest;
use App\Http\Requests\SubDebt\SyncSubDebtsRequest;
use App\Http\Requests\SubDebt\UpdateSubDebtRequest;
use App\Http\Resources\SubDebtResource;
use App\Models\Debt;
use App\Models\SubDebt;
use App\Services\SubDebtManagementService;
use App\Traits\SubDebt\DocumentsApi;
use App\Traits\SubDebt\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Sub debts are created, edited and removed as a set together with their debt (see the debt endpoints);
 * this controller lists, adds, edits and removes them one at a time.
 */
class SubDebtController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected SubDebtManagementService $subDebtManagementService,
    ) {}

    public function index(Request $request, Debt $debt): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;

        return SubDebtResource::collection($this->subDebtManagementService->paginate(
            $debt,
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'summary' => $this->subDebtManagementService->summary($debt),
            'deletable_total' => $this->subDebtManagementService->countMatching($debt, $search),
        ]);
    }

    public function store(StoreSubDebtRequest $request, Debt $debt): JsonResponse
    {
        $subDebt = $this->subDebtManagementService->create($debt, $request->validated());

        return (new SubDebtResource($subDebt))->response()->setStatusCode(201);
    }

    public function bulkDestroy(BulkDeleteSubDebtRequest $request, Debt $debt): Response
    {
        if ($request->boolean('all')) {
            $this->subDebtManagementService->deleteMatching($debt, $request->validated('search'));
        } else {
            $this->subDebtManagementService->deleteMany($debt, $request->validated('ids'));
        }

        return response()->noContent();
    }

    public function generate(Request $request, Debt $debt): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return SubDebtResource::collection($this->generateSampleSubDebts($debt, $validated['count']));
    }

    public function destroy(Debt $debt, SubDebt $subDebt): Response
    {
        $this->subDebtManagementService->delete($subDebt);

        return response()->noContent();
    }

    public function forgive(Debt $debt, SubDebt $subDebt): SubDebtResource
    {
        abort_if($subDebt->is_paid, 422, __('sub-debts.errors.already_settled'));

        return new SubDebtResource($this->subDebtManagementService->forgive($subDebt));
    }

    public function sync(SyncSubDebtsRequest $request, Debt $debt): AnonymousResourceCollection
    {
        $this->subDebtManagementService->replace($debt, $request->validated('sub_debts'));

        return SubDebtResource::collection($this->subDebtManagementService->all($debt))->additional([
            'summary' => $this->subDebtManagementService->summary($debt),
        ]);
    }

    public function show(Debt $debt, SubDebt $subDebt): SubDebtResource
    {
        return new SubDebtResource($subDebt);
    }

    public function update(UpdateSubDebtRequest $request, Debt $debt, SubDebt $subDebt): SubDebtResource
    {
        return new SubDebtResource($this->subDebtManagementService->update($subDebt, $request->validated()));
    }
}
