<?php

namespace App\Repositories\Receivable;

use App\Models\Receivable;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReceivableRepositoryInterface
{
    /**
     * Find a receivable by ID.
     */
    public function find(int $id): ?Receivable;

    /**
     * Paginate receivables, optionally filtered by search and sorted by column.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Delete receivables by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every receivable matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void;

    /**
     * Count receivables matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int;

    /**
     * Create a new receivable.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Receivable;

    /**
     * Update an existing receivable.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Receivable $receivable, array $data): Receivable;

    /**
     * Delete a receivable.
     */
    public function delete(Receivable $receivable): void;
}
