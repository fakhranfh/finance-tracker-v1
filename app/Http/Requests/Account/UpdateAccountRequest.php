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
            'adjust_by' => ['nullable', Rule::in(['record', 'balance'])],
            'balance' => ['required', 'numeric'],
            'color' => ['sometimes', 'string', Rule::in(config('account.colors'))],
            'icon' => ['sometimes', 'string', Rule::in(array_keys(config('account.icons')))],
        ];
    }
}
