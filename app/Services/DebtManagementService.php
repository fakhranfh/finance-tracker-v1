<?php

namespace App\Services;

use App\Models\Debt;
use App\Repositories\Debt\DebtRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class DebtManagementService
{
    public function __construct(
        protected DebtRepositoryInterface $debtRepository,
    ) {}

    /**
     * List debts, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->debtRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single debt by ID.
     */
    public function find(int $id): ?Debt
    {
        return $this->debtRepository->find($id);
    }

    /**
     * Create a new debt.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Debt
    {
        return $this->debtRepository->create($data);
    }

    /**
     * Update an existing debt.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Debt $debt, array $data): Debt
    {
        return $this->debtRepository->update($debt, $data);
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
     * Delete every debt matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->debtRepository->deleteMatching($search);
    }

    /**
     * Count debts matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->debtRepository->countMatching($search, $exceptId);
    }
}
