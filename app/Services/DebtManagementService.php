<?php

namespace App\Services;

use App\Enums\DebtType;
use App\Enums\RecordType;
use App\Models\Debt;
use App\Repositories\Debt\DebtRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class DebtManagementService
{
    public function __construct(
        protected DebtRepositoryInterface $debtRepository,
        protected SubDebtManagementService $subDebtService,
        protected RecordManagementService $recordService,
    ) {}

    /**
     * List debts, paginated, optionally filtered and sorted.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LengthAwarePaginator
    {
        return $this->debtRepository->paginate($perPage, $search, $sort, $direction, $filters);
    }

    /**
     * Find a single debt by ID.
     */
    public function find(int $id): ?Debt
    {
        return $this->debtRepository->find($id);
    }

    /**
     * Create a new debt, together with its sub debts when `sub_debts` is given. With `create_record`
     * the money moving into or out of the account is recorded too (not linked, so it is no payment).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Debt
    {
        $subDebts = Arr::pull($data, 'sub_debts');
        $createRecord = (bool) Arr::pull($data, 'create_record');

        return $this->debtRepository->transaction(function () use ($data, $subDebts, $createRecord): Debt {
            $debt = $this->debtRepository->create($data);

            if ($subDebts) {
                $this->subDebtService->replace($debt, $subDebts);
                $debt->refresh();
            }

            if ($createRecord) {
                // Borrowing brings money in, lending sends it out: the opposite of paying the debt off.
                $this->recordService->create([
                    'type' => $debt->type === DebtType::Debt ? RecordType::Income->value : RecordType::Expense->value,
                    'account_id' => $debt->account_id,
                    'amount' => $debt->amount,
                    'note' => $debt->name,
                    'recorded_at' => now(),
                ]);
            }

            return $debt;
        });
    }

    /**
     * Update an existing debt; when `sub_debts` is given the debt's sub debts are replaced by it
     * (an empty list removes them all).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Debt $debt, array $data): Debt
    {
        $subDebts = Arr::pull($data, 'sub_debts');

        return $this->debtRepository->transaction(function () use ($debt, $data, $subDebts): Debt {
            $debt = $this->debtRepository->update($debt, $data);

            if ($subDebts !== null) {
                $this->subDebtService->replace($debt, $subDebts);
                $debt->refresh();
            }

            return $debt;
        });
    }

    /**
     * Forgive a debt: the rest of it and of its sub debts is written off and it counts as settled.
     */
    public function forgive(Debt $debt): Debt
    {
        return $this->debtRepository->transaction(fn (): Debt => $this->debtRepository->forgive($debt));
    }

    /**
     * Delete a debt.
     */
    public function delete(Debt $debt): void
    {
        $this->debtRepository->delete($debt);
    }

    /**
     * Delete multiple debts by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->debtRepository->deleteMany($ids);
    }

    /**
     * Delete every debt matching the search and filters (all when empty).
     *
     * @param  array<string, mixed>  $filters
     */
    public function deleteMatching(?string $search = null, array $filters = []): void
    {
        $this->debtRepository->deleteMatching($search, $filters);
    }

    /**
     * Count debts matching the search and filters, optionally excluding one ID.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countMatching(?string $search = null, ?int $exceptId = null, array $filters = []): int
    {
        return $this->debtRepository->countMatching($search, $exceptId, $filters);
    }
}
