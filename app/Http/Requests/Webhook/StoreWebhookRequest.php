<?php

namespace App\Http\Requests\Webhook;

use App\Enums\RecordType;
use App\Rules\IpList;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
            'can_create_accounts' => ['sometimes', 'boolean'],
            'allowed_ips' => ['nullable', 'string', 'max:2000', new IpList],
            'allowed_account_ids' => ['nullable', 'array'],
            'allowed_account_ids.*' => ['integer', Rule::exists('accounts', 'id')],
            'allowed_types' => ['nullable', 'array'],
            'allowed_types.*' => [Rule::enum(RecordType::class)],
        ];
    }
}
