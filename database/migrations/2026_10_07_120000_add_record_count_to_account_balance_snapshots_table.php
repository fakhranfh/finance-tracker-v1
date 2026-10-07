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
            $table->unsignedInteger('record_count')->default(0);
        });

        DB::statement(<<<'SQL'
            update account_balance_snapshots s set record_count = (
                select count(*) from records r
                where (r.account_id = s.account_id or r.to_account_id = s.account_id) and r.id <= s.last_record_id
            )
            SQL);
    }

    public function down(): void
    {
        Schema::table('account_balance_snapshots', function (Blueprint $table): void {
            $table->dropColumn('record_count');
        });
    }
};
