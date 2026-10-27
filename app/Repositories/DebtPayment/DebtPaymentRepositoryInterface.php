<?php

namespace App\Repositories\DebtPayment;

use App\Models\DebtPayment;
use Illuminate\Pagination\LengthAwarePaginator;

interface DebtPaymentRepositoryInterface
{
    /**
     * Paginate every payment of a debt (or one of its sub debts): the linked records and the payments saved
     * without a record, as plain rows with a `source` of `record` or `payment`.
     *
     * @return LengthAwarePaginator<int, \stdClass>
     */
    public function paginateForDebt(int $debtId, ?int $subDebtId, int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator;

    /**
     * Find a payment saved without a record, only when it belongs to the debt.
     */
    public function findForDebt(int $debtId, int $id): ?DebtPayment;

    /**
     * Save a payment without a record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DebtPayment;

    /**
     * Delete a payment saved without a record.
     */
    public function delete(DebtPayment $payment): void;

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
