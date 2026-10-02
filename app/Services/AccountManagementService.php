<?php

namespace App\Services;

use App\Models\Account;
use App\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountManagementService
{
    public function __construct(
        protected AccountRepositoryInterface $accountRepository,
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
        return $this->accountRepository->create($data);
    }

    /**
     * Update an existing account.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Account $account, array $data): Account
    {
        return $this->accountRepository->update($account, $data);
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
