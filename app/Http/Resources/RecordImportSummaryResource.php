<?php

namespace App\Http\Resources;

use App\Models\RecordImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecordImport
 */
class RecordImportSummaryResource extends JsonResource
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
            'id' => $import->id,
            'filename' => $import->filename,
            'step' => $import->step->value,
            'row_count' => $import->row_count,
            'updated_at' => $import->updated_at?->toIso8601String(),
        ];
    }
}
