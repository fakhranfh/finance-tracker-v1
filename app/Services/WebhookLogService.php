<?php

namespace App\Services;

use App\Models\WebhookLog;
use App\Repositories\WebhookLog\WebhookLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class WebhookLogService
{
    /** Logs kept per webhook; older ones are dropped. */
    private const KEEP = 500;

    /** Payloads whose JSON is longer than this are not stored. */
    private const MAX_PAYLOAD_LENGTH = 5000;

    public function __construct(
        protected WebhookLogRepositoryInterface $webhookLogRepository,
    ) {}

    /**
     * Log a call to a webhook public endpoint.
     *
     * @param  array<string, mixed>  $payload
     */
    public function record(int $webhookId, int $statusCode, ?string $ip, ?string $origin, array $payload, ?string $message = null, ?int $recordId = null): WebhookLog
    {
        $json = json_encode($payload);

        $log = $this->webhookLogRepository->create([
            'webhook_id' => $webhookId,
            'status_code' => $statusCode,
            'ip' => $ip,
            'origin' => $origin !== null ? Str::limit($origin, 255, '') : null,
            'message' => $message !== null ? Str::limit($message, 255, '') : null,
            'payload' => $json !== false && strlen($json) <= self::MAX_PAYLOAD_LENGTH ? $payload : null,
            'record_id' => $recordId,
        ]);

        $this->webhookLogRepository->trim($webhookId, self::KEEP);

        return $log;
    }

    /**
     * List a webhook logs, paginated.
     */
    public function paginate(int $webhookId, int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        return $this->webhookLogRepository->paginate($webhookId, $perPage, $search, $sort, $direction);
    }

    /**
     * Remove every log of a webhook.
     */
    public function clear(int $webhookId): void
    {
        $this->webhookLogRepository->deleteForWebhook($webhookId);
    }
}
