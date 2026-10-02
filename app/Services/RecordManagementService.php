<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Models\Record;
use App\Repositories\Record\RecordRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class RecordManagementService
{
    public function __construct(
        protected RecordRepositoryInterface $recordRepository,
    ) {}

    /**
     * List records, paginated, optionally filtered and sorted.
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        return $this->recordRepository->paginate($perPage, $search, $sort, $direction);
    }

    /**
     * Find a single record by ID.
     */
    public function find(int $id): ?Record
    {
        return $this->recordRepository->find($id);
    }

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Record
    {
        return $this->recordRepository->create($this->normalize($data));
    }

    /**
     * Update an existing record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Record $record, array $data): Record
    {
        return $this->recordRepository->update($record, $this->normalize($data));
    }

    /**
     * Delete a record.
     */
    public function delete(Record $record): void
    {
        $this->recordRepository->delete($record);
    }

    /**
     * Delete multiple records by ID.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(array $ids): void
    {
        $this->recordRepository->deleteMany($ids);
    }

    /**
     * Delete every record matching the search filter (all when null).
     */
    public function deleteMatching(?string $search = null): void
    {
        $this->recordRepository->deleteMatching($search);
    }

    /**
     * Count records matching the search filter, optionally excluding one ID.
     */
    public function countMatching(?string $search = null, ?int $exceptId = null): int
    {
        return $this->recordRepository->countMatching($search, $exceptId);
    }

    /**
     * Only transfers have a destination account.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if ($data['type'] !== RecordType::Transfer->value) {
            $data['to_account_id'] = null;
        }

        return $data;
    }
}
