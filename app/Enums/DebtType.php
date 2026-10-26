<?php

namespace App\Enums;

enum DebtType: string
{
    case Debt = 'debt';
    case Receivable = 'receivable';

    public function label(): string
    {
        return __('debts.types.'.$this->value);
    }
}
