<?php

namespace App\Http\Resources;

use App\Models\CycleEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CycleEntry
 */
class CycleEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CycleEntry $entry */
        $entry = $this->resource;

        return [
            'id' => $entry->id,
            'cycle_type_id' => $entry->cycle_type_id,
            'name' => $entry->name,
            'price' => $entry->price,
            'bought_at' => $entry->bought_at,
            'finished_at' => $entry->finished_at,
            'type' => $this->whenLoaded('cycleType', fn () => [
                'id' => $entry->cycleType->id,
                'name' => $entry->cycleType->name,
                'category_id' => $entry->cycleType->category_id,
                'avg_days' => $entry->getAttribute('type_avg_days'),
            ]),
        ];
    }
}
