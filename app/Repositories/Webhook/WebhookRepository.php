<?php

namespace App\Repositories\Webhook;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class WebhookRepository implements WebhookRepositoryInterface
{
    /** How long a secret stays cached; it is also dropped as soon as the webhook changes. */
    private const SECRET_TTL_HOURS = 24;

    public function cachedSecret(Webhook $webhook): ?string
    {
        // The ciphertext is cached, not the plain secret; an empty string means "no secret".
        $cipher = Cache::remember(
            $this->secretCacheKey($webhook->id),
            now()->addHours(self::SECRET_TTL_HOURS),
            fn (): string => $webhook->getRawOriginal('secret') ?? '',
        );

        return $cipher === '' ? null : Crypt::decryptString($cipher);
    }

    private function secretCacheKey(int $webhookId): string
    {
        return "webhook.secret.{$webhookId}";
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

        foreach ($ids as $id) {
            Cache::forget($this->secretCacheKey($id));
        }
    }

    public function deleteMatching(?string $search = null): void
    {
        $query = Webhook::query();

        $this->applySearch($query, $search);

        $ids = (clone $query)->pluck('id');

        $query->delete();

        $ids->each(fn (int $id) => Cache::forget($this->secretCacheKey($id)));
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
        Cache::forget($this->secretCacheKey($webhook->id));

        return $webhook;
    }

    public function delete(Webhook $webhook): void
    {
        $webhook->delete();
        Cache::forget($this->secretCacheKey($webhook->id));
    }
}
