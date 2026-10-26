<?php

namespace App\Http\Requests\Debt;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteDebtRequest extends FormRequest
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
        return [
            'all' => ['sometimes', 'boolean'],
            'search' => ['nullable', 'string'],
            ...DebtFilterRules::rules(),
            'ids' => ['required_without:all', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:debts,id'],
        ];
    }
}
