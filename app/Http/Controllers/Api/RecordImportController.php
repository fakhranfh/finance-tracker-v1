<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Record\ImportRecordsRequest;
use App\Http\Requests\Record\PreviewRecordImportRequest;
use App\Http\Requests\Record\RecordImportValuesRequest;
use App\Services\RecordImportService;
use App\Traits\RecordImport\DocumentsApi;
use Illuminate\Http\JsonResponse;

class RecordImportController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected RecordImportService $recordImportService,
    ) {}

    public function preview(PreviewRecordImportRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->recordImportService->preview($request->file('file'))]);
    }

    public function values(RecordImportValuesRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->recordImportService->values(
            $request->validated('token'),
            $request->validated('mapping'),
        )]);
    }

    public function store(ImportRecordsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->recordImportService->import(
            $request->validated('token'),
            $request->validated('mapping'),
            $request->validated('accounts'),
            $request->validated('categories'),
            $request->user()?->timezone ?: config('app.timezone', 'UTC'),
        )]);
    }
}
