<?php

namespace App\Repositories\WebhookLog;

use App\Models\WebhookLog;
use Illuminate\Pagination\LengthAwarePaginator;

interface WebhookLogRepositoryInterface
{
    /**
     * Store a log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WebhookLog;

    /**
     * Paginate a webhook logs, newest first unless sorted, optionally filtered by search.
     */
    public function paginate(int $webhookId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator;

    /**
     * Remove every log of a webhook.
     */
    public function deleteForWebhook(int $webhookId): void;

    /**
     * Keep only the newest $keep logs of a webhook.
     */
    public function trim(int $webhookId, int $keep): void;
}
