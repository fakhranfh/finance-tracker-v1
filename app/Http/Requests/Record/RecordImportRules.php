<?php

namespace App\Http\Requests\Record;

use App\Enums\RecordImportStep;
use Illuminate\Validation\Rule;

/**
 * Shared validation for the record import steps: the column mapping, saved progress and the value choices.
 */
final class RecordImportRules
{
    private const FIELDS = ['recorded_at', 'amount', 'type', 'account', 'to_account', 'category', 'note'];

    /**
     * The column mapping to run with: the date and amount columns are required.
     *
     * @return array<string, array<mixed>>
     */
    public static function mapping(): array
    {
        return [
            'mapping' => ['required', 'array'],
            ...self::columnRules(['recorded_at', 'amount'], ['required', 'integer', 'min:0']),
            ...self::columnRules(array_diff(self::FIELDS, ['recorded_at', 'amount']), ['nullable', 'integer', 'min:0']),
        ];
    }

    /**
     * Progress saved along the way: any part of it, the mapping still incomplete.
     *
     * @return array<string, array<mixed>>
     */
    public static function progress(): array
    {
        return [
            'step' => ['sometimes', Rule::enum(RecordImportStep::class)],
            'mapping' => ['sometimes', 'array'],
            ...self::columnRules(self::FIELDS, ['nullable', 'integer', 'min:0']),
            'choices' => ['sometimes', 'array', 'max:10000'],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function values(): array
    {
        return [
            'types' => ['present', 'array'],
            'types.income' => ['nullable', 'string', 'max:500'],
            'types.expense' => ['nullable', 'string', 'max:500'],
            'types.transfer' => ['nullable', 'string', 'max:500'],
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

    /**
     * @param  iterable<int, string>  $fields
     * @param  array<int, string>  $rules
     * @return array<string, array<int, string>>
     */
    private static function columnRules(iterable $fields, array $rules): array
    {
        $result = [];

        foreach ($fields as $field) {
            $result["mapping.{$field}"] = $rules;
        }

        return $result;
    }
}
