<?php

namespace App\Http\Requests\Record;

use App\Enums\RecordType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecordRequest extends FormRequest
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
            'type' => ['required', Rule::enum(RecordType::class)],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => [
                Rule::requiredIf($this->input('type') === RecordType::Transfer->value),
                'nullable', 'integer', 'different:account_id', 'exists:accounts,id',
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'note' => ['nullable', 'string'],
            'recorded_at' => ['required', 'date'],
        ];
    }
}
