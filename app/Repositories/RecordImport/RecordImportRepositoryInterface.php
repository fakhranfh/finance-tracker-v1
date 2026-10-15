<?php

namespace App\Repositories\RecordImport;

use App\Models\RecordImport;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface RecordImportRepositoryInterface
{
    /**
     * A user's unfinished imports, most recently worked on first.
     *
     * @return Collection<int, RecordImport>
     */
    public function forUser(int $userId): Collection;

    /**
     * Find one of the user's imports.
     */
    public function find(int $userId, int $id): ?RecordImport;

    /**
     * Create an import.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RecordImport;

    /**
     * Update an import.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(RecordImport $import, array $data): RecordImport;

    /**
     * Delete an import.
     */
    public function delete(RecordImport $import): void;

    /**
     * Delete the imports not touched since the given moment and return the paths of their files.
     *
     * @return array<int, string>
     */
    public function deleteOlderThan(CarbonInterface $moment): array;
}
