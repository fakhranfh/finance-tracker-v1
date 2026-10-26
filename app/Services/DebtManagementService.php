<?php

namespace App\Services;

use App\Models\Debt;
use App\Repositories\Debt\DebtRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class DebtManagementService
{
    public function __construct(
        protected DebtRepositoryInterface $debtRepository,
        protected SubDebtManagementService $subDebtService,
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
     * Create a new debt, together with its sub debts when `sub_debts` is given.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Debt
    {
        $subDebts = Arr::pull($data, 'sub_debts');

        return $this->debtRepository->transaction(function () use ($data, $subDebts): Debt {
            $debt = $this->debtRepository->create($data);

            if ($subDebts) {
                $this->subDebtService->replace($debt, $subDebts);
                $debt->refresh();
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
