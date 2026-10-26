<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Receivable\BulkDeleteReceivableRequest;
use App\Http\Requests\Receivable\StoreReceivableRequest;
use App\Http\Requests\Receivable\UpdateReceivableRequest;
use App\Http\Resources\ReceivableResource;
use App\Models\Receivable;
use App\Services\ReceivableManagementService;
use App\Traits\Receivable\DocumentsApi;
use App\Traits\Receivable\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ReceivableController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected ReceivableManagementService $receivableManagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;

        return ReceivableResource::collection($this->receivableManagementService->paginate(
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'deletable_total' => $this->receivableManagementService->countMatching($search),
        ]);
    }

    public function bulkDestroy(BulkDeleteReceivableRequest $request): Response
    {
        if ($request->boolean('all')) {
            $this->receivableManagementService->deleteMatching($request->validated('search'));
        } else {
            $this->receivableManagementService->deleteMany($request->validated('ids'));
        }

        return response()->noContent();
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return ReceivableResource::collection($this->generateSampleReceivables($validated['count']));
    }

    public function show(Receivable $receivable): ReceivableResource
    {
        return new ReceivableResource($receivable);
    }

    public function store(StoreReceivableRequest $request): JsonResponse
    {
        $receivable = $this->receivableManagementService->create($request->validated());

        return (new ReceivableResource($receivable))->response()->setStatusCode(201);
    }

    public function update(UpdateReceivableRequest $request, Receivable $receivable): ReceivableResource
    {
        return new ReceivableResource($this->receivableManagementService->update($receivable, $request->validated()));
    }

    public function destroy(Receivable $receivable): Response
    {
        $this->receivableManagementService->delete($receivable);

        return response()->noContent();
    }
}
