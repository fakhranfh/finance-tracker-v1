<?php

namespace App\Repositories\CycleEntry;

use App\Models\CycleEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CycleEntryRepositoryInterface
{
    /**
     * Paginate the purchases of an item type, oldest first, optionally filtered by product name.
     */
    public function paginate(int $typeId, int $perPage, ?string $search = null): LengthAwarePaginator;

    /**
     * Every purchase of the item type, oldest first.
     *
     * @return Collection<int, CycleEntry>
     */
    public function allForType(int $typeId): Collection;

    /**
     * Create a purchase of the item type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(int $typeId, array $data): CycleEntry;

    /**
     * Update a purchase.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CycleEntry $entry, array $data): CycleEntry;

    /**
     * Delete a purchase.
     */
    public function delete(CycleEntry $entry): void;

    /**
     * Delete the given purchases of the item type.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteMany(int $typeId, array $ids): void;

    /**
     * Load the purchase's item type together with all of that type's purchases.
     */
    public function loadType(CycleEntry $entry): CycleEntry;
}
