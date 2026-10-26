<?php

namespace App\Http\Requests\Debt;

use App\Models\Debt;
use Illuminate\Validation\Rule;

/**
 * Shared sub debt rules of the store and update debt requests. When sub debts are sent, the debt's amount, paid
 * amount and paid flag are derived from them on the server, so the values sent for the debt itself are ignored.
 */
trait ValidatesSubDebts
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function subDebtRules(?Debt $debt): array
    {
        return [
            'sub_debts' => ['sometimes', 'array', 'max:50'],
            'sub_debts.*.id' => $debt
                ? ['nullable', 'integer', Rule::exists('sub_debts', 'id')->where('debt_id', $debt->id)]
                : ['prohibited'],
            'sub_debts.*.name' => ['required', 'string', 'max:255'],
            'sub_debts.*.amount' => ['required', 'integer', 'gt:0', 'max:999999999999'],
            'sub_debts.*.paid_amount' => ['nullable', 'integer', 'min:0', 'lte:sub_debts.*.amount'],
            'sub_debts.*.due_date' => ['nullable', 'date_format:Y-m-d'],
            'sub_debts.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
