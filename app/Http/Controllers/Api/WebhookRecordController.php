<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\StoreWebhookRecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Webhook;
use App\Services\WebhookLogService;
use App\Services\WebhookManagementService;
use App\Traits\WebhookRecord\DocumentsApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WebhookRecordController extends Controller
{
    use DocumentsApi;

    /** Header carrying the webhook token. */
    private const TOKEN_HEADER = 'X-Webhook-Token';

    /** Header carrying the HMAC signature, required when the webhook has a secret. */
    private const SIGNATURE_HEADER = 'X-Webhook-Signature';

    public function __construct(
        protected WebhookManagementService $webhookManagementService,
        protected WebhookLogService $webhookLogService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) $request->header(self::TOKEN_HEADER);
        $webhook = $token !== '' ? $this->webhookManagementService->findByToken($token) : null;

        abort_if($webhook === null, 401, __('webhooks.errors.invalid_token'));

        $origin = $request->headers->get('Origin') ?: $request->headers->get('Referer');

        try {
            abort_unless($webhook->is_active, 404, __('webhooks.logs.inactive'));

            abort_unless(
                $this->webhookManagementService->verifySignature($webhook, $request->getContent(), $request->header(self::SIGNATURE_HEADER)),
                401,
                __('webhooks.errors.invalid_signature'),
            );

            // Resolving the form request validates the payload.
            $data = app(StoreWebhookRecordRequest::class)->validated();
            $record = $this->webhookManagementService->addRecord($webhook, $data, $request->ip());
        } catch (ValidationException $e) {
            $this->log($request, $webhook, 422, $origin, collect($e->errors())->flatten()->first());

            throw $e;
        } catch (HttpExceptionInterface $e) {
            $this->log($request, $webhook, $e->getStatusCode(), $origin, $e->getMessage() ?: null);

            throw $e;
        }

        $this->log($request, $webhook, 201, $origin, null, $record->id);

        return (new RecordResource($record))->response()->setStatusCode(201);
    }

    private function log(Request $request, Webhook $webhook, int $status, ?string $origin, ?string $message, ?int $recordId = null): void
    {
        $this->webhookLogService->record($webhook->id, $status, $request->ip(), $origin, $request->all(), $message, $recordId);
    }
}
