<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * A row of the payments list: a record that pays the debt off, or a payment saved without a record.
 * The `id` of a payment without a record is its negated id, so it never collides with a record id.
 */
class DebtPaymentRowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \stdClass $row */
        $row = $this->resource;
        $isRecord = $row->source === 'record';

        return [
            'id' => $isRecord ? (int) $row->id : -(int) $row->id,
            'record_id' => $isRecord ? (int) $row->id : null,
            'debt_id' => (int) $row->debt_id,
            'sub_debt_id' => $row->sub_debt_id === null ? null : (int) $row->sub_debt_id,
            'sub_debt' => $row->sub_debt,
            'account' => $row->account,
            'category' => $row->category,
            'amount' => (int) $row->amount,
            'note' => $row->note,
            'recorded_at' => Carbon::parse($row->recorded_at, config('app.timezone'))->toIso8601String(),
            'created_at' => $row->created_at === null ? null : Carbon::parse($row->created_at, config('app.timezone'))->toIso8601String(),
        ];
    }
}
