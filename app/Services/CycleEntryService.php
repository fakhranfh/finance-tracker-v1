<?php

namespace App\Services;

use App\Models\CycleEntry;
use App\Models\CycleType;
use App\Repositories\CycleEntry\CycleEntryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CycleEntryService
{
    public function __construct(
        protected CycleEntryRepositoryInterface $cycleEntryRepository,
        protected CycleUsageCalculator $calculator,
    ) {}

    /**
     * A page of the item type's purchases (oldest first), and the item type itself carrying `avg_days`, `monthly`
     * and `last_entry` (the latest purchase, to prefill a new one), all worked out from every purchase of the type.
     *
     * @return array{entries: LengthAwarePaginator<int, CycleEntry>, type: CycleType}
     */
    public function paginate(CycleType $type, int $perPage = 10, ?string $search = null): array
    {
        $all = $this->cycleEntryRepository->allForType($type->id);
        $usage = $this->calculator->forEntries($all);
        $latest = $all->last();

        $type->setAttribute('avg_days', $usage['avg_days']);
        $type->setAttribute('monthly', $usage['monthly']);
        $type->setAttribute('last_entry', $latest ? ['name' => $latest->name, 'price' => $latest->price] : null);

        return ['entries' => $this->cycleEntryRepository->paginate($type->id, $perPage, $search), 'type' => $type];
    }

    /**
     * A purchase with its item type's `type_avg_days`, so the estimated run-out date can be worked out.
     */
    public function detail(CycleEntry $entry): CycleEntry
    {
        $entry = $this->cycleEntryRepository->loadType($entry);

        $entry->setAttribute('type_avg_days', $this->calculator->forEntries($entry->cycleType->entries)['avg_days']);

        return $entry;
    }

    /**
     * Add a purchase to the item type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(CycleType $type, array $data): CycleEntry
    {
        return $this->detail($this->cycleEntryRepository->create($type->id, $data));
    }

    /**
     * Update a purchase.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CycleEntry $entry, array $data): CycleEntry
    {
        return $this->detail($this->cycleEntryRepository->update($entry, $data));
    }

    /**
     * Mark the purchase as run out on the given date.
     */
    public function finish(CycleEntry $entry, string $finishedAt): CycleEntry
    {
        return $this->detail($this->cycleEntryRepository->update($entry, ['finished_at' => $finishedAt]));
    }

    public function delete(CycleEntry $entry): void
    {
        $this->cycleEntryRepository->delete($entry);
    }

    /**
     * Delete several purchases of the item type at once.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(CycleType $type, array $ids): void
    {
        $this->cycleEntryRepository->deleteMany($type->id, $ids);
    }
}
