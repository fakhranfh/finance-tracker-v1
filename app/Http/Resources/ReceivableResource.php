<?php

namespace App\Http\Resources;

use App\Models\Receivable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Receivable
 */
class ReceivableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Receivable $receivable */
        $receivable = $this->resource;

        return [
            'id' => $receivable->id,
            'name' => $receivable->name,
            'amount' => $receivable->amount,
            'paid_amount' => $receivable->paid_amount,
            'due_date' => $receivable->due_date,
            'notes' => $receivable->notes,
            'is_paid' => $receivable->is_paid,
            'created_at' => $receivable->created_at?->toIso8601String(),
        ];
    }
}
