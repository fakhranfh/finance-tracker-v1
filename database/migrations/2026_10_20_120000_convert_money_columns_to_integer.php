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
        $grammar = DB::getQueryGrammar();

        foreach ($this->columns as [$table, $column]) {
            $wrappedTable = $grammar->wrap($table);
            $wrappedColumn = $grammar->wrap($column);

            DB::statement("UPDATE {$wrappedTable} SET {$wrappedColumn} = ROUND({$wrappedColumn}) WHERE {$wrappedColumn} <> ROUND({$wrappedColumn})");
            $this->retype($table, $column, 'bigint');
        }
    }

    public function down(): void
    {
        foreach ($this->columns as [$table, $column, $precision]) {
            $this->retype($table, $column, "numeric({$precision}, 2)");
        }
    }

    /**
     * Changes the column type and keeps its nullability and default. PostgreSQL converts with USING;
     * MySQL/MariaDB rewrites the definition, so the current nullability and default are read first.
     */
    private function retype(string $table, string $column, string $type): void
    {
        $grammar = DB::getQueryGrammar();
        $wrappedTable = $grammar->wrap($table);
        $wrappedColumn = $grammar->wrap($column);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE {$wrappedTable} ALTER COLUMN {$wrappedColumn} TYPE {$type} USING {$wrappedColumn}::{$type}");

            return;
        }

        $definition = DB::selectOne(
            'SELECT IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );
        $null = $definition->is_nullable === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $definition->column_default === null ? '' : " DEFAULT {$definition->column_default}";

        DB::statement("ALTER TABLE {$wrappedTable} MODIFY {$wrappedColumn} ".strtoupper($type)." {$null}{$default}");
    }
};
