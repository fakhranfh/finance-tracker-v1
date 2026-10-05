<?php

namespace App\Repositories\Record;

use App\Models\Record;
use Illuminate\Pagination\LengthAwarePaginator;

interface RecordRepositoryInterface
{
    /**
     * Find a record by ID.
     */
    public function find(int $id): ?Record;

    /**
     * Paginate records, optionally filtered by search and filters, and sorted by column.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LengthAwarePaginator;

    /**
     * Delete records by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every record matching the search and filters (all when none apply).
     *
     * @param  array<string, mixed>  $filters
     */
    public function deleteMatching(?string $search = null, array $filters = []): void;

    /**
     * Count records matching the search and filters, optionally excluding one ID.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countMatching(?string $search = null, ?int $exceptId = null, array $filters = []): int;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Record;

    /**
     * Update an existing record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Record $record, array $data): Record;

    /**
     * Delete a record.
     */
    public function delete(Record $record): void;
}
