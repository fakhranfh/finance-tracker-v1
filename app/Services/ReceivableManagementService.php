<?php

namespace App\Services;

use App\Models\Receivable;
use App\Repositories\Receivable\ReceivableRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ReceivableManagementService
{
    public function __construct(
        protected ReceivableRepositoryInterface $receivableRepository,
    ) {}

    /**
     * List receivables, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->receivableRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single receivable by ID.
     */
    public function find(int $id): ?Receivable
    {
        return $this->receivableRepository->find($id);
    }

    /**
     * Create a new receivable.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Receivable
    {
        return $this->receivableRepository->create($data);
    }

    /**
     * Update an existing receivable.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Receivable $receivable, array $data): Receivable
    {
        return $this->receivableRepository->update($receivable, $data);
    }

    /**
     * Delete a receivable.
     */
    public function delete(Receivable $receivable): void
    {
        $this->receivableRepository->delete($receivable);
    }

    /**
     * Delete multiple receivables by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->receivableRepository->deleteMany($ids);
    }

    /**
     * Delete every receivable matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->receivableRepository->deleteMatching($search);
    }

    /**
     * Count receivables matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->receivableRepository->countMatching($search, $exceptId);
    }
}
