<?php

namespace App\Http\Requests\Record;

use App\Enums\RecordType;
use Illuminate\Validation\Rule;

/**
 * Shared validation for the record list filters (type, account, date range).
 */
final class RecordFilterRules
{
    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', Rule::enum(RecordType::class)],
            'account_ids' => ['sometimes', 'array'],
            'account_ids.*' => ['integer', 'exists:accounts,id'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
