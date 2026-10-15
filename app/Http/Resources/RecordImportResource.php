<?php

namespace App\Http\Resources;

use App\Models\RecordImport;
use Illuminate\Http\Request;

/**
 * An import with everything needed to carry on from where the user left off.
 *
 * @mixin RecordImport
 */
class RecordImportResource extends RecordImportSummaryResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var RecordImport $import */
        $import = $this->resource;

        return [
            ...parent::toArray($request),
            'columns' => $import->columns,
            'sample' => $import->sample,
            'mapping' => $import->mapping ?? [],
            'values' => $import->values,
            'choices' => $import->choices ?? [],
        ];
    }
}
