<?php

namespace App\Http\Requests\SubDebt;

use App\Models\SubDebt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A sub debt takes its own details, a paid amount or the paid flag; each field is optional but one must be sent.
 * The paid amount can never exceed the (new) amount.
 */
class UpdateSubDebtRequest extends FormRequest
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
        /** @var SubDebt $subDebt */
        $subDebt = $this->route('subDebt');
        $amount = (int) $this->input('amount', $subDebt->amount);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'integer', 'gt:0', 'max:999999999999'],
            'paid_amount' => ['sometimes', 'required', 'integer', 'min:0', 'max:'.$amount],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_paid' => ['required_without_all:name,amount,paid_amount,due_date,note', 'boolean'],
        ];
    }
}
