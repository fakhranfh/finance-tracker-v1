<?php

namespace App\Http\Requests\Webhook;

use App\Enums\RecordType;
use App\Http\Requests\Record\StoreRecordRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Same payload as a record created from the app, except the source account is given by
 * name (account_name) instead of ID and only income and expense are accepted. Likewise the
 * category by name (category_name). Whether the names exist is checked against the webhook,
 * which may create unknown ones.
 */
class StoreWebhookRecordRequest extends StoreRecordRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['account_id'], $rules['to_account_id'], $rules['category_id']);

        // Transfers need a second account, which is not part of the signed payload.
        $rules['type'] = ['required', Rule::in([RecordType::Income->value, RecordType::Expense->value])];

        return [
            'account_name' => ['required', 'string', 'max:255'],
            'category_name' => ['nullable', 'string', 'max:255'],
        ] + $rules;
    }
}
