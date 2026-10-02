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
     * Paginate records, optionally filtered by search and sorted by column.
     */
    public function paginate(int $perPage, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator;

    /**
     * Delete records by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void;

    /**
     * Delete every record matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void;

    /**
     * Count records matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int;

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
