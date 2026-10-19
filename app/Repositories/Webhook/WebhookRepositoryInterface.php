<?php

namespace App\Repositories\Webhook;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WebhookRepositoryInterface
{
    /**
     * Find a webhook by ID.
     */
    public function find(int $id): ?Webhook;

    /**
     * Find a webhook by its token, active or not.
     */
    public function findByToken(string $token): ?Webhook;

    /**
     * Every webhook, active or not.
     *
     * @return Collection<int, Webhook>
     */
    public function all(): Collection;

    /**
     * Remember a request nonce for $ttlSeconds. False when it was already used (a replay).
     */
    public function claimNonce(int $webhookId, string $nonce, int $ttlSeconds): bool;

    /**
     * Paginate webhooks, optionally filtered by search and sorted by column.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Delete webhooks by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every webhook matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void;

    /**
     * Count webhooks matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int;

    /**
     * Create a new webhook.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Webhook;

    /**
     * Update an existing webhook.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Webhook $webhook, array $data): Webhook;

    /**
     * Delete a webhook.
     */
    public function delete(Webhook $webhook): void;
}
