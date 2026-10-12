<?php

namespace App\Http\Resources;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Goal
 */
class GoalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Goal $goal */
        $goal = $this->resource;

        return [
            'id' => $goal->id,
            'name' => $goal->name,
            'target_amount' => $goal->target_amount,
            'saved_amount' => $goal->saved_amount,
            'desired_date' => $goal->desired_date,
            'color' => $goal->color,
            'icon' => $goal->icon,
            'status' => $goal->status->value,
            'note' => $goal->note,
            'created_at' => $goal->created_at?->toIso8601String(),
        ];
    }
}
