<?php

namespace App\Http\Resources;

use App\Models\SubDebt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SubDebt
 */
class SubDebtResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SubDebt $subDebt */
        $subDebt = $this->resource;

        return [
            'id' => $subDebt->id,
            'debt_id' => $subDebt->debt_id,
            'name' => $subDebt->name,
            'amount' => $subDebt->amount,
            'paid_amount' => $subDebt->paid_amount,
            'due_date' => $subDebt->due_date,
            'is_paid' => $subDebt->is_paid,
            'note' => $subDebt->note,
            'created_at' => $subDebt->created_at?->toIso8601String(),
        ];
    }
}
