<?php

namespace App\Http\Resources;

use App\Models\DebtPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A payment saved without a record, shaped like a row of the payments list.
 *
 * @mixin DebtPayment
 */
class UnrecordedPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DebtPayment $payment */
        $payment = $this->resource;

        return [
            'id' => -$payment->id,
            'record_id' => null,
            'debt_id' => $payment->debt_id,
            'sub_debt_id' => $payment->sub_debt_id,
            'sub_debt' => $payment->subDebt?->name,
            'account' => null,
            'category' => null,
            'amount' => $payment->amount,
            'note' => $payment->note,
            'recorded_at' => $payment->paid_at->toIso8601String(),
            'created_at' => $payment->created_at?->toIso8601String(),
        ];
    }
}
