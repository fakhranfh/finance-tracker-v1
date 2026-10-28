<?php

namespace App\Http\Resources;

use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A record seen as a payment of a debt.
 *
 * @mixin Record
 */
class DebtPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Record $record */
        $record = $this->resource;

        return [
            'id' => $record->id,
            'debt_id' => $record->debt_id,
            'sub_debt_id' => $record->sub_debt_id,
            'sub_debt' => $record->subDebt?->name,
            'account_id' => $record->account_id,
            'account' => $record->account->name,
            'category_id' => $record->category_id,
            'category' => $record->category?->name,
            'amount' => $record->amount,
            'note' => $record->note,
            'recorded_at' => $record->recorded_at->toIso8601String(),
            'created_at' => $record->created_at?->toIso8601String(),
        ];
    }
}
