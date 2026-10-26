<?php

namespace App\Http\Requests\Debt;

use App\Enums\DebtType;
use Illuminate\Validation\Rule;

/**
 * Shared validation for the debt list filters.
 */
final class DebtFilterRules
{
    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', Rule::enum(DebtType::class)],
        ];
    }
}
