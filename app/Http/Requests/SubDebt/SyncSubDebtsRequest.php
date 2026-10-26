<?php

namespace App\Http\Requests\SubDebt;

use App\Http\Requests\Debt\ValidatesSubDebts;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Replaces the sub debts of a debt; the debt's amount, paid amount and paid flag follow them.
 */
class SyncSubDebtsRequest extends FormRequest
{
    use ValidatesSubDebts;

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
        return [
            ...$this->subDebtRules($this->route('debt')),
            'sub_debts' => ['present', 'array', 'max:50'],
        ];
    }
}
