<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\BulkDeleteWebhookRequest;
use App\Http\Requests\Webhook\StoreWebhookRecordRequest;
use App\Http\Requests\Webhook\StoreWebhookRequest;
use App\Http\Requests\Webhook\UpdateWebhookRequest;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use App\Services\WebhookManagementService;
use App\Traits\Webhook\DocumentsApi;
use App\Traits\Webhook\GeneratesSampleData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    use DocumentsApi, GeneratesSampleData;

    public function __construct(
        protected WebhookManagementService $webhookManagementService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->value() ?: null;

        return WebhookResource::collection($this->webhookManagementService->paginate(
            perPage: min(max($request->integer('per_page', 10), 1), 100),
            search: $search,
            sort: $request->string('sort')->value() ?: null,
            direction: $request->string('direction', 'asc')->value(),
        ))->additional([
            'deletable_total' => $this->webhookManagementService->countMatching($search),
        ]);
    }

    public function bulkDestroy(BulkDeleteWebhookRequest $request): Response
    {
        if ($request->boolean('all')) {
            $this->webhookManagementService->deleteMatching($request->validated('search'));
        } else {
            $this->webhookManagementService->deleteMany($request->validated('ids'));
        }

        return response()->noContent();
    }

    public function generate(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return WebhookResource::collection($this->generateSampleWebhooks($validated['count']));
    }

    public function test(StoreWebhookRecordRequest $request, Webhook $webhook): JsonResponse
    {
        // The same signed request a real call sends (see WebhookRecordController).
        $signature = $request->input('signature');
        $timestamp = (string) $request->header(WebhookManagementService::TIMESTAMP_HEADER);
        $nonce = (string) $request->header(WebhookManagementService::NONCE_HEADER);

        abort_unless(
            is_string($signature)
                && $this->webhookManagementService->isFresh($timestamp, $nonce)
                && $this->webhookManagementService->signatureMatches($webhook, $request->all(), $signature, $timestamp, $nonce),
            401,
            __('webhooks.errors.invalid_signature'),
        );

        if (! $webhook->is_active) {
            return response()->json(['message' => __('webhooks.test.inactive')], 409);
        }

        $this->webhookManagementService->prepareRecord($webhook, $request->validated(), $request->input('ip'), dryRun: true);

        return response()->json([
            'message' => __('webhooks.test.success'),
            'data' => $request->validated(),
        ]);
    }

    public function show(Webhook $webhook): WebhookResource
    {
        return new WebhookResource($webhook);
    }

    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = $this->webhookManagementService->create($request->validated());

        return (new WebhookResource($webhook))->response()->setStatusCode(201);
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook): WebhookResource
    {
        return new WebhookResource($this->webhookManagementService->update($webhook, $request->validated()));
    }

    public function rotateToken(Webhook $webhook): WebhookResource
    {
        return new WebhookResource($this->webhookManagementService->rotateToken($webhook));
    }

    public function destroy(Webhook $webhook): Response
    {
        $this->webhookManagementService->delete($webhook);

        return response()->noContent();
    }
}
