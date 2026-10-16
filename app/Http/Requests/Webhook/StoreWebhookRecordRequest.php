<?php

namespace App\Http\Requests\Webhook;

use App\Http\Requests\Record\StoreRecordRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Same payload as a record created from the app, except the source account
 * is given by name (account_name) instead of ID.
 */
class StoreWebhookRecordRequest extends StoreRecordRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['account_id']);

        return ['account_name' => ['required', 'string', Rule::exists('accounts', 'name')]] + $rules;
    }
}
