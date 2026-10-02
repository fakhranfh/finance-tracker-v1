<?php

namespace App\Http\Resources;

use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Record
 */
class RecordResource extends JsonResource
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
            'type' => $record->type->value,
            'account_id' => $record->account_id,
            'account' => $record->account->name,
            'to_account_id' => $record->to_account_id,
            'to_account' => $record->toAccount?->name,
            'amount' => $record->amount,
            'note' => $record->note,
            'recorded_at' => $record->recorded_at->toIso8601String(),
            'created_at' => $record->created_at?->toIso8601String(),
        ];
    }
}
