<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'adjust_by' => ['nullable', Rule::in(['initial_balance', 'record'])],
            'initial_balance' => ['required_unless:adjust_by,record', 'numeric', 'min:0'],
            'balance' => ['required_if:adjust_by,record', 'numeric'],
        ];
    }
}
