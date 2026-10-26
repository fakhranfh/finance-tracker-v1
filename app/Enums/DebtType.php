<?php

namespace App\Enums;

enum DebtType: string
{
    case Debt = 'debt';
    case Receivable = 'receivable';

    /**
     * The kind of record that pays this off: money going out repays a debt, money coming in settles a receivable.
     */
    public function recordType(): RecordType
    {
        return match ($this) {
            self::Debt => RecordType::Expense,
            self::Receivable => RecordType::Income,
        };
    }

    public function label(): string
    {
        return __('debts.types.'.$this->value);
    }
}
