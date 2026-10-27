<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\SubDebt;
use App\Repositories\Debt\DebtRepositoryInterface;
use App\Repositories\SubDebt\SubDebtRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubDebtManagementService
{
    public function __construct(
        protected DebtRepositoryInterface $debtRepository,
        protected SubDebtRepositoryInterface $subDebtRepository,
    ) {}

    /**
     * List a debt's sub debts, paginated, optionally filtered and sorted.
     */
    public function paginate(Debt $debt, int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->subDebtRepository->paginate($debt->id, $perPage, $search, $sort, $direction);
    }

    /**
     * Every sub debt of the debt.
     *
     * @return Collection<int, SubDebt>
     */
    public function all(Debt $debt): Collection
    {
        return $this->subDebtRepository->getForDebt($debt->id);
    }

    /**
     * How many sub debts the debt has, how many are paid and their combined amount and paid amount.
     *
     * @return array{total: int, paid: int, amount_total: int, paid_total: int}
     */
    public function summary(Debt $debt): array
    {
        return $this->subDebtRepository->summary($debt->id);
    }

    /**
     * Count the debt's sub debts matching the search.
     */
    public function countMatching(Debt $debt, ?string $search = null): int
    {
        return $this->subDebtRepository->countMatching($debt->id, $search);
    }

    /**
     * Create one sub debt for the debt; the debt's amount follows the sub debts.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Debt $debt, array $data): SubDebt
    {
        return $this->subDebtRepository->transaction(function () use ($debt, $data): SubDebt {
            $subDebt = $this->subDebtRepository->create($debt->id, $data);
            $this->refreshTotals($debt);

            return $subDebt;
        });
    }

    /**
     * Update a sub debt's details; the debt's amount follows the sub debts. What was paid comes from its payments.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SubDebt $subDebt, array $data): SubDebt
    {
        return $this->subDebtRepository->transaction(function () use ($subDebt, $data): SubDebt {
            $subDebt = $this->subDebtRepository->update($subDebt, $data);
            $this->refreshTotals($subDebt->debt);

            return $subDebt;
        });
    }

    /**
     * Forgive a sub debt: the rest of it is written off and it counts as settled.
     */
    public function forgive(SubDebt $subDebt): SubDebt
    {
        return $this->subDebtRepository->forgive($subDebt);
    }

    /**
     * Delete a sub debt; its payments stay as records, and the debt's amount follows the sub debts left.
     */
    public function delete(SubDebt $subDebt): void
    {
        $this->subDebtRepository->transaction(function () use ($subDebt): void {
            $debt = $subDebt->debt;

            $this->subDebtRepository->delete($subDebt);
            $this->refreshTotals($debt);
        });
    }

    /**
     * Delete several of the debt's sub debts by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(Debt $debt, array $ids): void
    {
        $this->subDebtRepository->transaction(function () use ($debt, $ids): void {
            $this->subDebtRepository->deleteMany($debt->id, $ids);
            $this->refreshTotals($debt);
        });
    }

    /**
     * Delete every sub debt of the debt matching the search (all when null).
     */
    public function deleteMatching(Debt $debt, ?string $search = null): void
    {
        $this->subDebtRepository->transaction(function () use ($debt, $search): void {
            $this->subDebtRepository->deleteMatching($debt->id, $search);
            $this->refreshTotals($debt);
        });
    }

    /**
     * Make the debt's sub debts match the given list, all or nothing: items with an id are updated, items without
     * one are created and the sub debts left out are removed. A non-empty list also sets the debt's amount from them.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function replace(Debt $debt, array $items): void
    {
        $this->subDebtRepository->transaction(function () use ($debt, $items): void {
            $existing = $this->subDebtRepository->getForDebt($debt->id)->keyBy('id');
            $kept = [];

            foreach ($items as $item) {
                $fields = [
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'due_date' => $item['due_date'] ?? null,
                    'note' => $item['note'] ?? null,
                ];
                $id = (int) ($item['id'] ?? 0);

                if ($existing->has($id)) {
                    $this->subDebtRepository->update($existing[$id], $fields);
                    $kept[] = $id;
                } else {
                    $kept[] = $this->subDebtRepository->create($debt->id, $fields)->id;
                }
            }

            $removed = $existing->keys()->diff($kept)->values()->all();

            if ($removed !== []) {
                $this->subDebtRepository->deleteMany($debt->id, $removed);
            }

            $this->refreshTotals($debt);
        });
    }

    /**
     * Set the debt's amount from its sub debts (left alone when it has none).
     */
    public function refreshTotals(Debt $debt): void
    {
        $summary = $this->subDebtRepository->summary($debt->id);

        if ($summary['total'] === 0) {
            return;
        }

        $this->debtRepository->update($debt, ['amount' => $summary['amount_total']]);
    }
}
