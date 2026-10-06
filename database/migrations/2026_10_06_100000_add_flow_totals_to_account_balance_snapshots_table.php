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
            $table->unsignedInteger('record_count')->default(0);
        });

        // Existing snapshots have no totals. Without a snapshot, reads fall back
        // to the records themselves, so dropping them is safe and they rebuild.
        DB::table('account_balance_snapshots')->delete();
    }

    public function down(): void
    {
        Schema::table('account_balance_snapshots', function (Blueprint $table): void {
            $table->dropColumn(['income', 'expense', 'transfer_in', 'transfer_out', 'record_count']);
        });
    }
};
