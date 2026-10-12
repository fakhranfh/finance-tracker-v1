<?php

namespace App\Http\Resources;

use App\Models\SubGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SubGoal
 */
class SubGoalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SubGoal $subGoal */
        $subGoal = $this->resource;

        return [
            'id' => $subGoal->id,
            'goal_id' => $subGoal->goal_id,
            'name' => $subGoal->name,
            'target_amount' => $subGoal->target_amount,
            'saved_amount' => $subGoal->saved_amount,
            'desired_date' => $subGoal->desired_date,
            'is_done' => $subGoal->is_done,
            'note' => $subGoal->note,
            'created_at' => $subGoal->created_at?->toIso8601String(),
        ];
    }
}
