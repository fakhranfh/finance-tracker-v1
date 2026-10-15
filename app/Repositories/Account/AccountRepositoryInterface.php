<?php

namespace App\Repositories\Account;

use App\Models\Account;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     * Reorder accounts: the given IDs take the sort slots they occupy, in this order.
     *
     * @param  array<int, int>  $ids
     */
    public function reorder(array $ids): void;

    /**
     * Delete accounts by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * IDs of the accounts matching the search filter (all when null).
     *
     * @return array<int, int>
     */
    public function idsMatching(?string $search = null): array;

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

    /**
     * The IDs of every account, optionally leaving one out.
     *
     * @return array<int, int>
     */
    public function allIds(?int $exceptId = null): array;

    /**
     * The sort order a newly created account takes: one after the last.
     */
    public function nextSortOrder(): int;

    /**
     * The names of the given accounts.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, string>
     */
    public function namesOf(array $ids): Collection;

    /**
     * Every account's ID and name, by name.
     *
     * @return Collection<int, Account>
     */
    public function allNamed(): Collection;
}
