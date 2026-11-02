<?php

namespace App\Http\Resources;

use App\Models\CycleType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CycleType
 */
class CycleTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CycleType $type */
        $type = $this->resource;

        return [
            'id' => $type->id,
            'category_id' => $type->category_id,
            'name' => $type->name,
            'icon' => $type->icon,
            'avg_days' => $type->getAttribute('avg_days'),
            'monthly' => $type->getAttribute('monthly'),
            'last_entry' => $type->getAttribute('last_entry'),
            'category' => $this->whenLoaded('category', fn () => ['id' => $type->category->id, 'name' => $type->category->name]),
        ];
    }
}
