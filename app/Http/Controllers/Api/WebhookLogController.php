<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WebhookLogResource;
use App\Models\Webhook;
use App\Services\WebhookLogService;
use App\Traits\WebhookLog\DocumentsApi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WebhookLogController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected WebhookLogService $webhookLogService,
    ) {}

    public function index(Request $request, Webhook $webhook): AnonymousResourceCollection
    {
        return WebhookLogResource::collection($this->webhookLogService->paginate(
            $webhook->id,
            min(max($request->integer('per_page', 10), 1), 100),
            $request->string('search')->value() ?: null,
            $request->string('sort')->value() ?: null,
            $request->string('direction', 'desc')->value(),
        ));
    }

    public function destroy(Webhook $webhook): Response
    {
        $this->webhookLogService->clear($webhook->id);

        return response()->noContent();
    }
}
