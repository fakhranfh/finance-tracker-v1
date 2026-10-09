<?php

namespace App\Http\Requests\Record;

use App\Enums\RecordType;
use Illuminate\Validation\Rule;

/**
 * Shared validation for the record list filters (type, account, category, date range).
 * A category filter may include "none" to match income and expense records without a category.
 */
final class RecordFilterRules
{
    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['sometimes', 'array'],
            'type.*' => [Rule::enum(RecordType::class)],
            'account_ids' => ['sometimes', 'array'],
            'account_ids.*' => ['integer', 'exists:accounts,id'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['string', 'regex:/^(\d+|none)$/'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'amount_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'amount_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
