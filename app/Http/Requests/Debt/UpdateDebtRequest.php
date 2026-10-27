<?php

namespace App\Http\Requests\Debt;

use App\Enums\DebtType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDebtRequest extends FormRequest
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
            'type' => ['required', Rule::enum(DebtType::class)],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'gt:0', 'max:999999999999'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            ...$this->subDebtRules($this->route('debt')),
        ];
    }
}
