<?php

namespace App\Repositories\SubDebt;

use App\Models\SubDebt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubDebtRepositoryInterface
{
    /**
     * Paginate a debt's sub debts, optionally filtered by search and sorted by column.
     */
    public function paginate(int $debtId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Every sub debt of the debt.
     *
     * @return Collection<int, SubDebt>
     */
    public function getForDebt(int $debtId): Collection;

    /**
     * Count a debt's sub debts, how many are paid and their combined amount and paid amount.
     *
     * @return array{total: int, paid: int, amount_total: int, paid_total: int}
     */
    public function summary(int $debtId): array;

    /**
     * Count the debt's sub debts matching the search.
     */
    public function countMatching(int $debtId, ?string $search = null): int;

    /**
     * Delete every sub debt of the debt matching the search (all when null).
     */
    public function deleteMatching(int $debtId, ?string $search = null): void;

    /**
     * Delete a sub debt.
     */
    public function delete(SubDebt $subDebt): void;

    /**
     * Delete the given sub debts of the debt.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(int $debtId, array $ids): void;

    /**
     * Create a new sub debt for the debt.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(int $debtId, array $data): SubDebt;

    /**
     * Update an existing sub debt.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SubDebt $subDebt, array $data): SubDebt;

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
