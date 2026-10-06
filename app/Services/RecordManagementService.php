<?php

namespace App\Services;

use App\Enums\RecordType;
use App\Models\Record;
use App\Repositories\Record\RecordRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RecordManagementService
{
    public function __construct(
        protected RecordRepositoryInterface $recordRepository,
        protected AccountLastBalanceService $lastBalance,
        protected AccountBalanceSnapshotService $snapshots,
        protected AccountMonthlyTotalsService $monthlyTotals,
    ) {}

    /**
     * List records, paginated, optionally filtered and sorted.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 10, ?string $search = null, ?string $sort = null, string $direction = 'asc', array $filters = []): LengthAwarePaginator
    {
        return $this->recordRepository->paginate($perPage, $search, $sort, $direction, $filters);
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
        $this->applyDeletion($this->recordRepository->deleteMany($ids));
    }

    /**
     * Delete every record matching the search and filters (all when none apply).
     *
     * @param  array<string, mixed>  $filters
     */
    public function deleteMatching(?string $search = null, array $filters = []): void
    {
        $this->applyDeletion($this->recordRepository->deleteMatching($search, $filters));
    }

    /**
     * Count records matching the search and filters, optionally excluding one ID.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countMatching(?string $search = null, ?int $exceptId = null, array $filters = []): int
    {
        return $this->recordRepository->countMatching($search, $exceptId, $filters);
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

    /**
     * Bring balances, snapshots and monthly totals in line with records removed in bulk.
     *
     * @param  array{account_ids: array<int, int|null>, first_id: ?int, effects: Collection<int, \stdClass>}  $deletion
     */
    private function applyDeletion(array $deletion): void
    {
        foreach ($deletion['effects'] as $effect) {
            $this->lastBalance->apply($effect->type, $effect->account_id, $effect->to_account_id, $effect->total, -1);
        }

        if ($deletion['first_id'] !== null) {
            $this->snapshots->invalidate($deletion['account_ids'], $deletion['first_id']);
        }

        $this->monthlyTotals->refresh($deletion['account_ids']);
    }
}
