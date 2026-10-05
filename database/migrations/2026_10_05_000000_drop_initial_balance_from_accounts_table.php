<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('update accounts set last_balance = initial_balance where last_balance is null');

        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('initial_balance');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->decimal('initial_balance', 14, 2)->default(0)->after('name');
        });

        DB::statement(<<<'SQL'
            update accounts set initial_balance = coalesce(last_balance, 0)
                - coalesce((select sum(case when r.type = 'income' then r.amount else -r.amount end)
                    from records r where r.account_id = accounts.id), 0)
                - coalesce((select sum(r.amount)
                    from records r where r.type = 'transfer' and r.to_account_id = accounts.id), 0)
            SQL);
    }
};
