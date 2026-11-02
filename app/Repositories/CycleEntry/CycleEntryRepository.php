<?php

namespace App\Repositories\CycleEntry;

use App\Models\CycleEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CycleEntryRepository implements CycleEntryRepositoryInterface
{
    public function paginate(int $typeId, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $query = CycleEntry::query()->where('cycle_type_id', $typeId);

        if ($search) {
            $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%']);
        }

        return $query->orderBy('bought_at')->orderBy('id')->paginate($perPage)->withQueryString();
    }

    public function allForType(int $typeId): Collection
    {
        return CycleEntry::query()->where('cycle_type_id', $typeId)->orderBy('bought_at')->orderBy('id')->get();
    }

    public function create(int $typeId, array $data): CycleEntry
    {
        return CycleEntry::query()->create([...$data, 'cycle_type_id' => $typeId]);
    }

    public function update(CycleEntry $entry, array $data): CycleEntry
    {
        $entry->update($data);

        return $entry;
    }

    public function delete(CycleEntry $entry): void
    {
        $entry->delete();
    }

    public function deleteMany(int $typeId, array $ids): void
    {
        CycleEntry::query()->where('cycle_type_id', $typeId)->whereIn('id', $ids)->delete();
    }

    public function loadType(CycleEntry $entry): CycleEntry
    {
        return $entry->load('cycleType.entries');
    }
}
