<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Models\Record;
use App\Models\Webhook;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\Webhook\WebhookRepositoryInterface;
use App\Rules\IpList;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebhookManagementService
{
    public function __construct(
        protected WebhookRepositoryInterface $webhookRepository,
        protected RecordManagementService $recordManagementService,
        protected AccountRepositoryInterface $accountRepository,
    ) {}

    /**
     * List webhooks, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->webhookRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single webhook by ID.
     */
    public function find(int $id): ?Webhook
    {
        return $this->webhookRepository->find($id);
    }

    /**
     * Create a new webhook.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Webhook
    {
        return $this->webhookRepository->create($this->normalize($data) + ['token' => Str::random(40)]);
    }

    /**
     * Find a webhook by its token, active or not.
     */
    public function findByToken(string $token): ?Webhook
    {
        return $this->webhookRepository->findByToken($token);
    }

    /**
     * Add a record through a webhook.
     *
     * @param  array<string, mixed>  $data
     */
    public function addRecord(Webhook $webhook, array $data, ?string $ip): Record
    {
        return $this->recordManagementService->create($this->prepareRecord($webhook, $data, $ip));
    }

    /**
     * Check a record payload against the webhook restrictions (IP, type and accounts, each 403
     * when not allowed) and resolve account_name to account_id. Returns the data to store.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareRecord(Webhook $webhook, array $data, ?string $ip): array
    {
        abort_unless($webhook->allowsIp($ip), 403, __('webhooks.errors.ip_not_allowed'));
        abort_unless($webhook->allowsType($data['type']), 403, __('webhooks.errors.type_not_allowed'));

        $account = $this->accountRepository->findByName($data['account_name']);

        if ($account === null) {
            throw ValidationException::withMessages([
                'account_name' => __('validation.exists', ['attribute' => 'account name']),
            ]);
        }

        unset($data['account_name']);
        $data['account_id'] = $account->id;

        if (($data['to_account_id'] ?? null) === $account->id) {
            throw ValidationException::withMessages([
                'to_account_id' => __('validation.different', ['attribute' => 'to account id', 'other' => 'account name']),
            ]);
        }

        $toAccountId = $data['type'] === RecordType::Transfer->value ? $data['to_account_id'] ?? null : null;

        abort_unless(
            $webhook->allowsAccount($account->id) && ($toAccountId === null || $webhook->allowsAccount((int) $toAccountId)),
            403,
            __('webhooks.errors.account_not_allowed'),
        );

        return $data;
    }

    /**
     * Update an existing webhook.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Webhook $webhook, array $data): Webhook
    {
        return $this->webhookRepository->update($webhook, $this->normalize($data));
    }

    /**
     * Delete a webhook.
     */
    public function delete(Webhook $webhook): void
    {
        $this->webhookRepository->delete($webhook);
    }

    /**
     * Delete multiple webhooks by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->webhookRepository->deleteMany($ids);
    }

    /**
     * Delete every webhook matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->webhookRepository->deleteMatching($search);
    }

    /**
     * Count webhooks matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->webhookRepository->countMatching($search, $exceptId);
    }

    /**
     * Turn the submitted IP, account and type lists into arrays (null when empty).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('allowed_ips', $data)) {
            $data['allowed_ips'] = IpList::parse($data['allowed_ips']) ?: null;
        }

        if (array_key_exists('allowed_account_ids', $data)) {
            $data['allowed_account_ids'] = array_values(array_unique(array_map('intval', $data['allowed_account_ids'] ?? []))) ?: null;
        }

        if (array_key_exists('allowed_types', $data)) {
            $data['allowed_types'] = array_values(array_unique($data['allowed_types'] ?? [])) ?: null;
        }

        return $data;
    }
}
