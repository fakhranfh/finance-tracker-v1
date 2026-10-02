<?php

namespace App\Repositories\Account;

use App\Models\Account;
use Illuminate\Pagination\LengthAwarePaginator;

interface AccountRepositoryInterface
{
    /**
     * Find a account by ID.
     */
    public function find(int $id): ?Account;

    /**
     * Paginate accounts, optionally filtered by search and sorted by column.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Delete accounts by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every account matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void;

    /**
     * Count accounts matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int;

    /**
     * Create a new account.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Account;

    /**
     * Update an existing account.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Account $account, array $data): Account;

    /**
     * Delete a account.
     */
    public function delete(Account $account): void;
}
