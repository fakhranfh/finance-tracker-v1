<?php

namespace App\Http\Resources;

use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Debt
 */
class DebtResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Debt $debt */
        $debt = $this->resource;

        return [
            'id' => $debt->id,
            'type' => $debt->type,
            'name' => $debt->name,
            'amount' => $debt->amount,
            'paid_amount' => $debt->paid_amount,
            'remaining_amount' => $debt->remaining_amount,
            'due_date' => $debt->due_date,
            'notes' => $debt->notes,
            'is_paid' => $debt->is_paid,
            'created_at' => $debt->created_at?->toIso8601String(),
        ];
    }
}
