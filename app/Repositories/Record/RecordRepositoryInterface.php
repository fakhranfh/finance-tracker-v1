<?php

namespace App\Repositories\Record;

use App\Models\Record;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     * Transfers linking the given accounts to accounts outside them, as seen from those outside accounts.
     *
     * @param  array<int, int>  $accountIds
     * @return array{counterparts: array<int, int|null>, transfers_out: Collection<int, \stdClass>, transfers_in: Collection<int, \stdClass>}
     */
    public function sharedTransfers(array $accountIds): array;

    /**
     * Delete records by ID and describe what was removed.
     *
     * @param  array<int, int>  $ids
     * @return array{account_ids: array<int, int|null>, first_id: ?int, effects: Collection<int, \stdClass>}
     */
    public function deleteMany(array $ids): array;

    /**
     * Delete every record matching the search and filters (all when none apply).
     *
     * @param  array<string, mixed>  $filters
     * @return array{account_ids: array<int, int|null>, first_id: ?int, effects: Collection<int, \stdClass>}
     */
    public function deleteMatching(?string $search = null, array $filters = []): array;

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
