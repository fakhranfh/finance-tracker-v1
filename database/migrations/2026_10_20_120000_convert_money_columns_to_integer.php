<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Money is stored in whole units: existing decimal(p, 2) amounts are rounded to the nearest
 * whole unit (half away from zero) and the columns become bigint. The rounding is lossy; the
 * down migration only restores the decimal type, not the cents.
 */
return new class extends Migration
{
    /**
     * [table, column, original precision]. Scale was 2 on every column.
     *
     * @var list<array{0: string, 1: string, 2: int}>
     */
    private array $columns = [
        ['accounts', 'last_balance', 14],
        ['records', 'amount', 12],
        ['account_balance_snapshots', 'balance', 14],
        ['account_balance_snapshots', 'income', 14],
        ['account_balance_snapshots', 'expense', 14],
        ['account_balance_snapshots', 'transfer_in', 14],
        ['account_balance_snapshots', 'transfer_out', 14],
        ['account_balance_snapshots', 'net', 14],
        ['account_monthly_totals', 'income', 14],
        ['account_monthly_totals', 'expense', 14],
        ['account_monthly_totals', 'transfer_in', 14],
        ['account_monthly_totals', 'transfer_out', 14],
        ['account_monthly_totals', 'net', 14],
        ['account_monthly_totals', 'balance', 14],
        ['account_yearly_totals', 'income', 14],
        ['account_yearly_totals', 'expense', 14],
        ['account_yearly_totals', 'transfer_in', 14],
        ['account_yearly_totals', 'transfer_out', 14],
        ['account_yearly_totals', 'net', 14],
        ['account_yearly_totals', 'balance', 14],
        ['goals', 'target_amount', 14],
        ['goals', 'saved_amount', 14],
        ['goal_savings', 'amount', 14],
        ['sub_goals', 'target_amount', 12],
        ['sub_goals', 'saved_amount', 14],
        ['sub_goals', 'saved_amount_before_done', 14],
    ];

    public function up(): void
    {
        foreach ($this->columns as [$table, $column]) {
            DB::statement("UPDATE \"{$table}\" SET \"{$column}\" = ROUND(\"{$column}\") WHERE \"{$column}\" <> ROUND(\"{$column}\")");
            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"{$column}\" TYPE bigint USING \"{$column}\"::bigint");
        }
    }

    public function down(): void
    {
        foreach ($this->columns as [$table, $column, $precision]) {
            DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"{$column}\" TYPE numeric({$precision}, 2) USING \"{$column}\"::numeric({$precision}, 2)");
        }
    }
};
