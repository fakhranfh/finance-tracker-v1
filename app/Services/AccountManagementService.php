<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Models\Account;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\Record\RecordRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AccountManagementService
{
    public function __construct(
        protected AccountRepositoryInterface $accountRepository,
        protected RecordManagementService $recordManagementService,
        protected RecordRepositoryInterface $recordRepository,
        protected AccountLastBalanceService $lastBalance,
        protected AccountBalanceSnapshotService $snapshots,
        protected AccountMonthlyTotalsService $monthlyTotals,
    ) {}

    /**
     * List accounts, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->accountRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single account by ID.
     */
    public function find(int $id): ?Account
    {
        return $this->accountRepository->find($id);
    }

    /**
     * Create a new account.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Account
    {
        return $this->accountRepository->create([
            'name' => $data['name'],
            'last_balance' => $data['balance'],
            'sort_order' => (Account::query()->max('sort_order') ?? 0) + 1,
            ...Arr::only($data, ['color', 'icon']),
        ]);
    }

    /**
     * Reorder accounts by the given IDs, keeping the positions they occupy.
     *
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void
    {
        $this->accountRepository->reorder($ids);
    }

    /**
     * Update an existing account. With `adjust_by` = `balance` the balance is
     * set directly; otherwise (`record`, the default) a changed balance is
     * recorded as an income or expense, so the history explains the difference.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Account $account, array $data): Account
    {
        if (($data['adjust_by'] ?? 'record') === 'balance') {
            return $this->refreshSnapshotIfBalanceChanged($this->accountRepository->update($account, [
                'name' => $data['name'],
                'last_balance' => $data['balance'],
                ...Arr::only($data, ['color', 'icon']),
            ]));
        }

        $account = $this->accountRepository->update($account, Arr::only($data, ['name', 'color', 'icon']));

        $difference = round((float) $data['balance'] - (float) $this->accountRepository->find($account->id)->balance, 2);

        if ($difference != 0) {
            $this->recordManagementService->create([
                'type' => $difference > 0 ? RecordType::Income->value : RecordType::Expense->value,
                'account_id' => $account->id,
                'amount' => abs($difference),
                'note' => __('accounts.adjustment_note'),
                'recorded_at' => now(),
            ]);
        }

        return $account;
    }

    /**
     * Delete a account.
     */
    public function delete(Account $account): void
    {
        $this->deleteMany([$account->id]);
    }

    /**
     * Delete multiple accounts by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $shared = $this->recordRepository->sharedTransfers($ids);

        $this->accountRepository->deleteMany($ids);

        $this->applyDeletion($shared);
    }

    /**
     * Delete every account matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->deleteMany($this->accountRepository->idsMatching($search));
    }

    /**
     * Count accounts matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->accountRepository->countMatching($search, $exceptId);
    }

    /**
     * Snapshots of an account go stale when its balance is set directly.
     */
    private function refreshSnapshotIfBalanceChanged(Account $account): Account
    {
        if ($account->wasChanged('last_balance')) {
            $this->snapshots->invalidate([$account->id]);
        }

        return $account;
    }

    /**
     * Deleting accounts removes the transfers they shared with other accounts,
     * so correct the counterparts' balances and snapshots and totals.
     *
     * @param  array{counterparts: array<int, int|null>, transfers_out: Collection<int, \stdClass>, transfers_in: Collection<int, \stdClass>}  $deletion
     */
    private function applyDeletion(array $deletion): void
    {
        foreach ($deletion['transfers_out'] as $transfer) {
            $this->lastBalance->adjust($transfer->to_account_id, $transfer->total, -1);
        }

        foreach ($deletion['transfers_in'] as $transfer) {
            $this->lastBalance->adjust($transfer->account_id, $transfer->total);
        }

        $this->snapshots->invalidate($deletion['counterparts']);
        $this->monthlyTotals->refresh($deletion['counterparts']);
    }
}
