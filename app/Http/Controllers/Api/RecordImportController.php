<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Record\ImportRecordsRequest;
use App\Http\Requests\Record\RecordImportValuesRequest;
use App\Http\Requests\Record\UpdateRecordImportRequest;
use App\Http\Requests\Record\UploadRecordImportRequest;
use App\Http\Resources\RecordImportResource;
use App\Http\Resources\RecordImportSummaryResource;
use App\Models\RecordImport;
use App\Services\RecordImportService;
use App\Traits\RecordImport\DocumentsApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RecordImportController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected RecordImportService $recordImportService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return RecordImportSummaryResource::collection($this->recordImportService->list($request->user()->id));
    }

    public function store(UploadRecordImportRequest $request): JsonResponse
    {
        $import = $this->recordImportService->upload($request->user()->id, $request->file('file'));

        return (new RecordImportResource($import))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $import): RecordImportResource
    {
        return new RecordImportResource($this->findOrFail($request, $import));
    }

    public function update(UpdateRecordImportRequest $request, int $import): RecordImportResource
    {
        return new RecordImportResource($this->recordImportService->save($this->findOrFail($request, $import), $request->validated()));
    }

    public function values(RecordImportValuesRequest $request, int $import): JsonResponse
    {
        $loaded = $this->recordImportService->loadValues(
            $this->findOrFail($request, $import),
            $request->validated('mapping'),
            $request->integer('offset'),
        );

        return response()->json([
            'data' => (new RecordImportResource($loaded['import']))->resolve(),
            'progress' => $loaded['progress'],
        ]);
    }

    public function run(ImportRecordsRequest $request, int $import): JsonResponse
    {
        $ran = $this->recordImportService->import(
            $this->findOrFail($request, $import),
            $request->validated('types') ?? [],
            $request->validated('accounts'),
            $request->validated('categories'),
            $request->user()?->timezone ?: config('app.timezone', 'UTC'),
            $request->integer('offset'),
        );

        return response()->json(['data' => $ran['result'], 'progress' => $ran['progress']]);
    }

    public function destroy(Request $request, int $import): Response
    {
        $this->recordImportService->discard($this->findOrFail($request, $import));

        return response()->noContent();
    }

    private function findOrFail(Request $request, int $id): RecordImport
    {
        return $this->recordImportService->find($request->user()->id, $id) ?? abort(404);
    }
}
