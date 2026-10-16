<?php

namespace App\Repositories\WebhookLog;

use App\Models\WebhookLog;
use Illuminate\Pagination\LengthAwarePaginator;

class WebhookLogRepository implements WebhookLogRepositoryInterface
{
    public function create(array $data): WebhookLog
    {
        return WebhookLog::query()->create($data);
    }

    public function paginate(int $webhookId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $query = WebhookLog::query()
            ->where('webhook_id', $webhookId)
            ->when($search, fn ($q) => $q->where(function ($inner) use ($search): void {
                $inner->where('message', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $inner->orWhere('status_code', (int) $search);
                }
            }));
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, ['status_code', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        }

        return $query->orderBy('id', $direction)->paginate($perPage)->withQueryString();
    }

    public function deleteForWebhook(int $webhookId): void
    {
        WebhookLog::query()->where('webhook_id', $webhookId)->delete();
    }

    public function trim(int $webhookId, int $keep): void
    {
        $oldestKept = WebhookLog::query()
            ->where('webhook_id', $webhookId)
            ->orderByDesc('id')
            ->skip($keep - 1)
            ->take(1)
            ->value('id');

        if ($oldestKept !== null) {
            WebhookLog::query()->where('webhook_id', $webhookId)->where('id', '<', $oldestKept)->delete();
        }
    }
}
