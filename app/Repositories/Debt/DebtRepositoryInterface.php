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
     * Paginate debts, optionally filtered by search and sorted by column.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Delete debts by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every debt matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void;

    /**
     * Count debts matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int;

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
}
