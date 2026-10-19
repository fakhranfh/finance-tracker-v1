<?php

namespace App\Repositories\Webhook;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class WebhookRepository implements WebhookRepositoryInterface
{
    public function all(): Collection
    {
        return Webhook::query()->get();
    }

    public function claimNonce(int $webhookId, string $nonce, int $ttlSeconds): bool
    {
        // add() is atomic: only the first caller with this nonce gets true.
        return Cache::add("webhook.nonce.{$webhookId}.".hash('sha256', $nonce), true, $ttlSeconds);
    }

    public function find(int $id): ?Webhook
    {
        return Webhook::query()->find($id);
    }

    public function findByToken(string $token): ?Webhook
    {
        return Webhook::query()->where('token', $token)->first();
    }

    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        $query = Webhook::query();

        $this->applySearch($query, $search);

        $sortable = ['name', 'token', 'is_active', 'created_at'];
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function deleteMany(array $ids): void
    {
        Webhook::query()->whereIn('id', $ids)->delete();
    }

    public function deleteMatching(?string $search = null): void
    {
        $query = Webhook::query();

        $this->applySearch($query, $search);

        $query->delete();
    }

    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        $query = Webhook::query()->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $this->applySearch($query, $search);

        return $query->count();
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function ($inner) use ($search): void {
            $inner->where('name', 'like', "%{$search}%")
                ->orWhere('token', 'like', "%{$search}%");
        });
    }

    public function create(array $data): Webhook
    {
        return Webhook::query()->create($data);
    }

    public function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->update($data);

        return $webhook;
    }

    public function delete(Webhook $webhook): void
    {
        $webhook->delete();
    }
}
