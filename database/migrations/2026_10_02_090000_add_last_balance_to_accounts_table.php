<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->decimal('last_balance', 14, 2)->nullable()->after('initial_balance');
        });

        DB::statement(<<<'SQL'
            update accounts set last_balance = initial_balance
                + coalesce((select sum(case when r.type = 'income' then r.amount else -r.amount end)
                    from records r where r.account_id = accounts.id), 0)
                + coalesce((select sum(r.amount)
                    from records r where r.type = 'transfer' and r.to_account_id = accounts.id), 0)
            SQL);
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('last_balance');
        });
    }
};
