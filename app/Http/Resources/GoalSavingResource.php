<?php

namespace App\Http\Resources;

use App\Models\GoalSaving;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GoalSaving
 */
class GoalSavingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var GoalSaving $saving */
        $saving = $this->resource;

        return [
            'id' => $saving->id,
            'goal_id' => $saving->goal_id,
            'sub_goal_id' => $saving->sub_goal_id,
            'type' => $saving->type,
            'sub_goal_name' => $saving->subGoal?->name,
            'amount' => $saving->amount,
            'created_at' => $saving->created_at?->toIso8601String(),
        ];
    }
}
