<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Record\BulkDeleteRecordRequest;
use App\Http\Requests\Record\RecordFilterRules;
use App\Http\Requests\Record\StoreRecordRequest;
use App\Http\Requests\Record\UpdateRecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use App\Services\RecordManagementService;
use App\Traits\Record\DocumentsApi;
use App\Traits\Record\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RecordController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected RecordManagementService $recordManagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;
        $filters = $request->validate(RecordFilterRules::rules());

        return RecordResource::collection($this->recordManagementService->paginate(
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
            filters: $filters,
        ))->additional([
            'deletable_total' => $this->recordManagementService->countMatching($search, filters: $filters),
        ]);
    }

    public function bulkDestroy(BulkDeleteRecordRequest $request): Response
    {
        if ($request->boolean('all')) {
            $this->recordManagementService->deleteMatching(
                $request->validated('search'),
                $request->safe()->only(array_keys(RecordFilterRules::rules())),
            );
        } else {
            $this->recordManagementService->deleteMany($request->validated('ids'));
        }

        return response()->noContent();
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'account_ids' => ['sometimes', 'array'],
            'account_ids.*' => ['integer', 'exists:accounts,id'],
        ]);

        return RecordResource::collection($this->generateSampleRecords(
            $validated['count'],
            $validated['account_ids'] ?? [],
        ));
    }

    public function show(Record $record): RecordResource
    {
        return new RecordResource($record);
    }

    public function store(StoreRecordRequest $request): JsonResponse
    {
        $record = $this->recordManagementService->create($request->validated());

        return (new RecordResource($record))->response()->setStatusCode(201);
    }

    public function update(UpdateRecordRequest $request, Record $record): RecordResource
    {
        return new RecordResource($this->recordManagementService->update($record, $request->validated()));
    }

    public function destroy(Record $record): Response
    {
        $this->recordManagementService->delete($record);

        return response()->noContent();
    }
}
