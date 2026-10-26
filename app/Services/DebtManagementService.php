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
