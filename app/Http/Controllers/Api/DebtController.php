<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Debt\BulkDeleteDebtRequest;
use App\Http\Requests\Debt\StoreDebtRequest;
use App\Http\Requests\Debt\UpdateDebtRequest;
use App\Http\Resources\DebtResource;
use App\Models\Debt;
use App\Services\DebtManagementService;
use App\Traits\Debt\DocumentsApi;
use App\Traits\Debt\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DebtController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected DebtManagementService $debtManagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;

        return DebtResource::collection($this->debtManagementService->paginate(
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'deletable_total' => $this->debtManagementService->countMatching($search),
        ]);
    }

    public function bulkDestroy(BulkDeleteDebtRequest $request): Response
    {
        if ($request->boolean('all')) {
            $this->debtManagementService->deleteMatching($request->validated('search'));
        } else {
            $this->debtManagementService->deleteMany($request->validated('ids'));
        }

        return response()->noContent();
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return DebtResource::collection($this->generateSampleDebts($validated['count']));
    }

    public function show(Debt $debt): DebtResource
    {
        return new DebtResource($debt);
    }

    public function store(StoreDebtRequest $request): JsonResponse
    {
        $debt = $this->debtManagementService->create($request->validated());

        return (new DebtResource($debt))->response()->setStatusCode(201);
    }

    public function update(UpdateDebtRequest $request, Debt $debt): DebtResource
    {
        return new DebtResource($this->debtManagementService->update($debt, $request->validated()));
    }

    public function destroy(Debt $debt): Response
    {
        $this->debtManagementService->delete($debt);

        return response()->noContent();
    }
}
