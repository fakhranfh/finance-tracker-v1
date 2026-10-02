<?php

namespace App\Enums;

enum RecordType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';

    public function label(): string
    {
        return __('records.types.'.$this->value);
    }
}
