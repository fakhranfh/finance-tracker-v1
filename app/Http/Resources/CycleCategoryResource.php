<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An expense category as the first level of Usage Cycle: its look and what its item types cost per month.
 *
 * @mixin Category
 */
class CycleCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'color' => $category->color,
            'icon' => $category->icon,
            'monthly' => (int) $category->getAttribute('cycle_monthly'),
        ];
    }
}
