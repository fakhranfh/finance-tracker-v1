<?php

namespace App\Http\Requests\DebtPayment;

use App\Models\Debt;
use App\Models\Record;
use App\Models\SubDebt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Pays a debt off with an existing record (`record_id`) or a new payment, which is saved as a record on an account
 * unless `create_record` is false. A debt split into sub debts is paid per sub debt, and nothing can be paid
 * beyond what is still owed.
 */
class StoreDebtPaymentRequest extends FormRequest
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
        $type = $debt->type->recordType()->value;

        return [
            'record_id' => ['nullable', 'integer', Rule::exists('records', 'id')->where('type', $type)->whereNull('debt_id')],
            'sub_debt_id' => ['nullable', 'integer', Rule::exists('sub_debts', 'id')->where('debt_id', $debt->id)],
            'create_record' => ['sometimes', 'boolean'],
            'account_id' => [Rule::requiredIf(fn (): bool => ! $this->filled('record_id') && $this->boolean('create_record', true)), 'nullable', 'integer', 'exists:accounts,id'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('type', $type)],
            'amount' => ['required_without:record_id', 'integer', 'gt:0', 'max:999999999999'],
            'note' => ['nullable', 'string'],
            'recorded_at' => ['required_without:record_id', 'date'],
        ];
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
            $subDebtId = $this->integer('sub_debt_id') ?: null;

            if ($subDebtId === null && $debt->subDebts()->exists()) {
                $validator->errors()->add('sub_debt_id', __('debt-payments.validation.sub_debt_required'));

                return;
            }

            $target = $subDebtId ? SubDebt::query()->findOrFail($subDebtId) : $debt;
            $recordId = $this->integer('record_id') ?: null;
            $paying = $recordId ? (int) Record::query()->whereKey($recordId)->value('amount') : $this->integer('amount');

            if ($paying > $target->remaining_amount) {
                $validator->errors()->add($recordId ? 'record_id' : 'amount', __('debt-payments.validation.exceeds_remaining', [
                    'remaining' => 'Rp '.number_format($target->remaining_amount, 0, ',', '.'),
                ]));
            }
        }];
    }
}
