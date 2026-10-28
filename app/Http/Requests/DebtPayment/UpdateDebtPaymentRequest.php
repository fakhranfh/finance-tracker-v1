<?php

namespace App\Http\Requests\DebtPayment;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Record;
use App\Models\SubDebt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Changes a payment, a record or one saved without a record. A debt split into sub debts is paid per sub debt, and nothing can be
 * paid beyond what is still owed, not counting what this payment already pays.
 */
class UpdateDebtPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Debt $debt */
        $debt = $this->route('debt');
        $recordRules = $this->paysWithRecord() ? [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('type', $debt->type->recordType()->value)],
        ] : [];

        return [
            ...$recordRules,
            'sub_debt_id' => ['nullable', 'integer', Rule::exists('sub_debts', 'id')->where('debt_id', $debt->id)],
            'amount' => ['required', 'integer', 'gt:0', 'max:999999999999'],
            'note' => ['nullable', 'string'],
            'recorded_at' => ['required', 'date'],
        ];
    }

    /**
     * A positive payment id is a record, which also has an account and a category.
     */
    private function paysWithRecord(): bool
    {
        return (int) $this->route('payment') > 0;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Debt $debt */
            $debt = $this->route('debt');
            $key = (int) $this->route('payment');
            $payment = $key < 0
                ? DebtPayment::query()->where('debt_id', $debt->id)->find(-$key)
                : Record::query()->where('debt_id', $debt->id)->find($key);

            // The controller answers 404 for a payment that is not there.
            if ($payment === null) {
                return;
            }

            $subDebtId = $this->integer('sub_debt_id') ?: null;

            if ($subDebtId === null && $debt->subDebts()->exists()) {
                $validator->errors()->add('sub_debt_id', __('debt-payments.validation.sub_debt_required'));

                return;
            }

            $target = $subDebtId ? SubDebt::query()->findOrFail($subDebtId) : $debt;
            $alreadyPaying = ($subDebtId === null || $payment->sub_debt_id === $subDebtId) ? $payment->amount : 0;
            $remaining = $target->remaining_amount + $alreadyPaying;

            if ($this->integer('amount') > $remaining) {
                $validator->errors()->add('amount', __('debt-payments.validation.exceeds_remaining', [
                    'remaining' => 'Rp '.number_format($remaining, 0, ',', '.'),
                ]));
            }
        }];
    }
}
