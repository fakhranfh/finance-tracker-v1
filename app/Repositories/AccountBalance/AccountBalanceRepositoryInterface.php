<?php

namespace App\Repositories\AccountBalance;

use Illuminate\Support\Collection;

interface AccountBalanceRepositoryInterface
{
    /**
     * Add a signed amount to an account's last balance.
     */
    public function adjustLastBalance(int $accountId, string $signedAmount): void;

    /**
     * Accounts whose last balance differs from the one computed from their
     * records, as rows of id, name, last_balance and actual_balance.
     *
     * @return Collection<int, object{id: int, name: string, last_balance: string|null, actual_balance: string}>
     */
    public function driftedAccounts(): Collection;

    /**
     * Overwrite an account's last balance.
     */
    public function setLastBalance(int $accountId, string $balance): void;

    /**
     * Delete snapshots covering the given record ID or later (all when null)
     * and return the IDs of the accounts they belonged to.
     *
     * @param  array<int, int>  $accountIds
     * @return Collection<int, int>
     */
    public function deleteSnapshots(array $accountIds, ?int $fromRecordId = null): Collection;

    /**
     * Count records touching the account that are newer than its latest snapshot.
     */
    public function countRecordsSinceSnapshot(int $accountId): int;

    /**
     * Initial balance of the account, or null when it does not exist.
     */
    public function initialBalance(int $accountId): ?string;

    /**
     * ID of the latest record touching the account (as source or destination).
     */
    public function latestRecordId(int $accountId): ?int;

    /**
     * Latest snapshot of the account, if any.
     *
     * @return array{last_record_id: int, balance: string}|null
     */
    public function latestSnapshot(int $accountId): ?array;

    /**
     * Start balance plus the effect of the records in (afterRecordId, uptoRecordId].
     */
    public function balanceAfterRecords(int $accountId, string $startBalance, int $afterRecordId, int $uptoRecordId): string;

    /**
     * Store a snapshot, ignoring one that already exists for the same record.
     */
    public function storeSnapshot(int $accountId, int $lastRecordId, string $balance): void;
}
