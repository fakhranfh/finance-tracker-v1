<?php

namespace App\Repositories\RecordImport;

use App\Models\RecordImport;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class RecordImportRepository implements RecordImportRepositoryInterface
{
    public function forUser(int $userId): Collection
    {
        return RecordImport::query()->where('user_id', $userId)->latest('updated_at')->latest('id')->get();
    }

    public function find(int $userId, int $id): ?RecordImport
    {
        return RecordImport::query()->where('user_id', $userId)->find($id);
    }

    public function create(array $data): RecordImport
    {
        return RecordImport::query()->create($data);
    }

    public function update(RecordImport $import, array $data): RecordImport
    {
        $import->update($data);

        return $import;
    }

    public function delete(RecordImport $import): void
    {
        $import->delete();
    }

    public function deleteOlderThan(CarbonInterface $moment): array
    {
        $stale = RecordImport::query()->where('updated_at', '<', $moment);
        $paths = (clone $stale)->pluck('path')->all();

        $stale->delete();

        return $paths;
    }
}
