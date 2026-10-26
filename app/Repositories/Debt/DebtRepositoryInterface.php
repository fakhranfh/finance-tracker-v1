<?php

namespace App\Repositories\Debt;

use App\Models\Debt;
use Illuminate\Pagination\LengthAwarePaginator;

interface DebtRepositoryInterface
{
    /**
     * Find a debt by ID.
     */
    public function find(int $id): ?Debt;

    /**
     * Paginate debts, optionally filtered by search and filters and sorted by column.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LengthAwarePaginator;

    /**
     * Delete debts by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every debt matching the search and filters (all when empty).
     *
     * @param  array<string, mixed>  $filters
     */
    public function deleteMatching(?string $search = null, array $filters = []): void;

    /**
     * Count debts matching the search and filters, optionally excluding one ID.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countMatching(?string $search = null, ?int $exceptId = null, array $filters = []): int;

    /**
     * Create a new debt.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Debt;

    /**
     * Update an existing debt.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Debt $debt, array $data): Debt;

    /**
     * Delete a debt.
     */
    public function delete(Debt $debt): void;

    /**
     * Run the callback inside a database transaction and return its result.
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    public function transaction(\Closure $callback): mixed;
}
