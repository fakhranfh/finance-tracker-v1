<?php

namespace App\Http\Requests\Record;

use Illuminate\Validation\Rule;

/**
 * Shared validation for the record import steps: the uploaded file's token and the column mapping.
 */
final class RecordImportRules
{
    /**
     * @return array<string, array<mixed>>
     */
    public static function mapping(): array
    {
        return [
            'token' => ['required', 'string', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(xlsx|xls|csv)$/'],
            'mapping' => ['required', 'array'],
            'mapping.recorded_at' => ['required', 'integer', 'min:0'],
            'mapping.amount' => ['required', 'integer', 'min:0'],
            'mapping.type' => ['nullable', 'integer', 'min:0'],
            'mapping.account' => ['nullable', 'integer', 'min:0'],
            'mapping.to_account' => ['nullable', 'integer', 'min:0'],
            'mapping.category' => ['nullable', 'integer', 'min:0'],
            'mapping.note' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function values(): array
    {
        return [
            'accounts' => ['present', 'array'],
            'accounts.*.value' => ['required', 'string', 'max:255'],
            'accounts.*.action' => ['required', Rule::in(['existing', 'create', 'unmapped'])],
            'accounts.*.id' => ['required_if:accounts.*.action,existing', 'nullable', 'integer', 'exists:accounts,id'],
            'accounts.*.name' => ['nullable', 'string', 'max:255'],
            'categories' => ['present', 'array'],
            'categories.*.value' => ['required', 'string', 'max:255'],
            'categories.*.action' => ['required', Rule::in(['existing', 'create', 'none'])],
            'categories.*.id' => ['required_if:categories.*.action,existing', 'nullable', 'integer', 'exists:categories,id'],
            'categories.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
