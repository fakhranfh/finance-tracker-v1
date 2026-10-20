<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Models\Account;
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
    /** Header carrying the unix time the request was signed at. */
    public const TIMESTAMP_HEADER = 'X-Webhook-Timestamp';

    /** Header carrying a unique value per request, accepted once. */
    public const NONCE_HEADER = 'X-Webhook-Nonce';

    /** Values covered by the signature, in signing order. */
    private const SIGNED_FIELDS = ['timestamp', 'nonce', 'type', 'account_name', 'category_id', 'amount', 'note', 'recorded_at'];

    /** How far a request timestamp may be from the server clock. */
    private const REPLAY_TOLERANCE_SECONDS = 300;

    private const NONCE_MIN_LENGTH = 16;

    private const NONCE_MAX_LENGTH = 128;

    public function __construct(
        protected WebhookRepositoryInterface $webhookRepository,
        protected RecordManagementService $recordManagementService,
        protected AccountRepositoryInterface $accountRepository,
        protected AccountManagementService $accountManagementService,
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
     * Whether the X-Webhook-Timestamp and X-Webhook-Nonce values are well formed and fresh: the
     * timestamp is unix seconds within REPLAY_TOLERANCE_SECONDS of now and the nonce is
     * NONCE_MIN_LENGTH to NONCE_MAX_LENGTH characters.
     */
    public function isFresh(string $timestamp, string $nonce): bool
    {
        return ctype_digit($timestamp)
            && abs(time() - (int) $timestamp) <= self::REPLAY_TOLERANCE_SECONDS
            && strlen($nonce) >= self::NONCE_MIN_LENGTH
            && strlen($nonce) <= self::NONCE_MAX_LENGTH;
    }

    /**
     * Find the webhook a signed request belongs to, active or not, or null.
     *
     * The request carries no webhook identifier: `signature` is the hex HMAC-SHA256,
     * keyed by the webhook token, of the timestamp, the nonce and the record fields (see
     * signedString()). The webhook is the one whose token reproduces that signature.
     *
     * @param  array<string, mixed>  $input  the JSON body
     */
    public function findBySignature(array $input, string $signature, string $timestamp, string $nonce): ?Webhook
    {
        foreach ($this->webhookRepository->all() as $webhook) {
            if ($this->signatureMatches($webhook, $input, $signature, $timestamp, $nonce)) {
                return $webhook;
            }
        }

        return null;
    }

    /**
     * Whether $signature is the token-keyed HMAC of this timestamp, nonce and the record fields.
     *
     * @param  array<string, mixed>  $input  the JSON body
     */
    public function signatureMatches(Webhook $webhook, array $input, string $signature, string $timestamp, string $nonce): bool
    {
        $signed = $this->signedString(['timestamp' => $timestamp, 'nonce' => $nonce] + $input);

        return hash_equals(hash_hmac('sha256', $signed, $webhook->token), $signature);
    }

    /**
     * Mark a nonce as used by this webhook so the request cannot be replayed. False when it was
     * already used. Nonces are remembered for twice the timestamp tolerance, after which the
     * timestamp check rejects the request anyway.
     */
    public function claimNonce(Webhook $webhook, string $nonce): bool
    {
        return $this->webhookRepository->claimNonce($webhook->id, $nonce, self::REPLAY_TOLERANCE_SECONDS * 2);
    }

    /**
     * The signed fields as "name=value" pairs joined with "&", in the fixed order of
     * SIGNED_FIELDS. Missing and null values are empty, other values are cast to strings.
     *
     * @param  array<string, mixed>  $input
     */
    private function signedString(array $input): string
    {
        $pairs = [];

        foreach (self::SIGNED_FIELDS as $field) {
            $value = $input[$field] ?? null;

            $pairs[] = $field.'='.match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                is_scalar($value) => (string) $value,
                $value === null => '',
                default => (string) json_encode($value),
            };
        }

        return implode('&', $pairs);
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
     * when not allowed) and resolve account_name to account_id. An unknown account is created
     * when the webhook may create accounts; a dry run only checks that it could be. Returns the
     * data to store.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareRecord(Webhook $webhook, array $data, ?string $ip, bool $dryRun = false): array
    {
        abort_unless($webhook->allowsIp($ip), 403, __('webhooks.errors.ip_not_allowed'));
        abort_unless($webhook->allowsType($data['type']), 403, __('webhooks.errors.type_not_allowed'));

        $account = $this->accountRepository->findByName($data['account_name']);
        $createAccount = $account === null && $webhook->can_create_accounts;

        if ($account === null && ! $createAccount) {
            throw ValidationException::withMessages([
                'account_name' => __('validation.exists', ['attribute' => 'account name']),
            ]);
        }

        if ($createAccount && ! $dryRun) {
            $account = $this->createAccount($webhook, $data['account_name']);
        }

        unset($data['account_name']);
        $data['account_id'] = $account?->id;

        if ($account !== null && ($data['to_account_id'] ?? null) === $account->id) {
            throw ValidationException::withMessages([
                'to_account_id' => __('validation.different', ['attribute' => 'to account id', 'other' => 'account name']),
            ]);
        }

        $toAccountId = $data['type'] === RecordType::Transfer->value ? $data['to_account_id'] ?? null : null;

        abort_unless(
            ($createAccount || $webhook->allowsAccount($account->id))
                && ($toAccountId === null || $webhook->allowsAccount((int) $toAccountId)),
            403,
            __('webhooks.errors.account_not_allowed'),
        );

        return $data;
    }

    /**
     * Create the account named in a record. When the webhook limits its accounts, the new one
     * joins that list so later records can still use it.
     */
    private function createAccount(Webhook $webhook, string $name): Account
    {
        $account = $this->accountManagementService->create(['name' => $name, 'balance' => 0]);

        if (($webhook->allowed_account_ids ?? []) !== []) {
            $this->webhookRepository->update($webhook, ['allowed_account_ids' => [...$webhook->allowed_account_ids, $account->id]]);
        }

        return $account;
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
