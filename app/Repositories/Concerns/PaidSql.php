<?php

namespace App\Repositories\Concerns;

/**
 * SQL for what has been paid on a debt or sub debt: the linked records plus the payments that were not saved as records.
 */
class PaidSql
{
    public static function debt(): string
    {
        return '(select coalesce(sum(records.amount), 0) from records where records.debt_id = debts.id)'
            .' + (select coalesce(sum(debt_payments.amount), 0) from debt_payments where debt_payments.debt_id = debts.id)';
    }

    public static function subDebt(): string
    {
        return '(select coalesce(sum(records.amount), 0) from records where records.sub_debt_id = sub_debts.id)'
            .' + (select coalesce(sum(debt_payments.amount), 0) from debt_payments where debt_payments.sub_debt_id = sub_debts.id)';
    }
}
