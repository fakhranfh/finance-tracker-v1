<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CycleEntry\BulkDeleteCycleEntryRequest;
use App\Http\Requests\CycleEntry\FinishCycleEntryRequest;
use App\Http\Requests\CycleEntry\StoreCycleEntryRequest;
use App\Http\Requests\CycleEntry\UpdateCycleEntryRequest;
use App\Http\Resources\CycleEntryResource;
use App\Http\Resources\CycleTypeResource;
use App\Models\CycleEntry;
use App\Models\CycleType;
use App\Services\CycleEntryService;
use App\Traits\CycleEntry\DocumentsApi;
use App\Traits\CycleEntry\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CycleEntryController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected CycleEntryService $cycleEntryService,
    ) {}

    public function index(Request $request, CycleType $type): AnonymousResourceCollection
    {
        $page = $this->cycleEntryService->paginate(
            $type,
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $request->string('search')->value() ?: null,
        );

        return CycleEntryResource::collection($page['entries'])->additional(['type' => new CycleTypeResource($page['type'])]);
    }

    public function generate(Request $request, CycleType $type): AnonymousResourceCollection
    {
        $validated = $request->validate(['count' => ['required', 'integer', 'min:1', 'max:50']]);

        return CycleEntryResource::collection($this->generateSampleEntries($type, $validated['count']));
    }

    public function store(StoreCycleEntryRequest $request, CycleType $type): JsonResponse
    {
        $entry = $this->cycleEntryService->create($type, $request->validated());

        return (new CycleEntryResource($entry))->response()->setStatusCode(201);
    }

    public function bulkDestroy(BulkDeleteCycleEntryRequest $request, CycleType $type): Response
    {
        $this->cycleEntryService->deleteMany($type, $request->validated('ids'));

        return response()->noContent();
    }

    public function show(CycleEntry $entry): CycleEntryResource
    {
        return new CycleEntryResource($this->cycleEntryService->detail($entry));
    }

    public function update(UpdateCycleEntryRequest $request, CycleEntry $entry): CycleEntryResource
    {
        return new CycleEntryResource($this->cycleEntryService->update($entry, $request->validated()));
    }

    public function finish(FinishCycleEntryRequest $request, CycleEntry $entry): CycleEntryResource
    {
        return new CycleEntryResource($this->cycleEntryService->finish($entry, $request->validated('finished_at')));
    }

    public function destroy(CycleEntry $entry): Response
    {
        $this->cycleEntryService->delete($entry);

        return response()->noContent();
    }
}
