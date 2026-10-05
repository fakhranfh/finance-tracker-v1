<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Models\Account;
use App\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class AccountManagementService
{
    public function __construct(
        protected AccountRepositoryInterface $accountRepository,
        protected RecordManagementService $recordManagementService,
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
        ]);
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
            return $this->accountRepository->update($account, [
                'name' => $data['name'],
                'last_balance' => $data['balance'],
            ]);
        }

        $account = $this->accountRepository->update($account, Arr::only($data, ['name']));

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
        $this->accountRepository->delete($account);
    }

    /**
     * Delete multiple accounts by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->accountRepository->deleteMany($ids);
    }

    /**
     * Delete every account matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->accountRepository->deleteMatching($search);
    }

    /**
     * Count accounts matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->accountRepository->countMatching($search, $exceptId);
    }
}
