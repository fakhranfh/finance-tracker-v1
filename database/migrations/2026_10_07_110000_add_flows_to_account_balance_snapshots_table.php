<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_balance_snapshots', function (Blueprint $table): void {
            $table->decimal('income', 14, 2)->default(0);
            $table->decimal('expense', 14, 2)->default(0);
            $table->decimal('transfer_in', 14, 2)->default(0);
            $table->decimal('transfer_out', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
        });

        // Backfill cumulative totals of the records up to each snapshot's last record.
        DB::statement(<<<'SQL'
            update account_balance_snapshots s set
                income = (select coalesce(sum(r.amount), 0) from records r
                    where r.account_id = s.account_id and r.type = 'income' and r.id <= s.last_record_id),
                expense = (select coalesce(sum(r.amount), 0) from records r
                    where r.account_id = s.account_id and r.type = 'expense' and r.id <= s.last_record_id),
                transfer_in = (select coalesce(sum(r.amount), 0) from records r
                    where r.to_account_id = s.account_id and r.type = 'transfer' and r.id <= s.last_record_id),
                transfer_out = (select coalesce(sum(r.amount), 0) from records r
                    where r.account_id = s.account_id and r.type = 'transfer' and r.id <= s.last_record_id)
            SQL);

        DB::statement('update account_balance_snapshots set net = income - expense + transfer_in - transfer_out');
    }

    public function down(): void
    {
        Schema::table('account_balance_snapshots', function (Blueprint $table): void {
            $table->dropColumn(['income', 'expense', 'transfer_in', 'transfer_out', 'net']);
        });
    }
};
