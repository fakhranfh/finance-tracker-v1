<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_debts', function (Blueprint $table): void {
            $table->timestamp('forgiven_at')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('sub_debts', function (Blueprint $table): void {
            $table->dropColumn('forgiven_at');
        });
    }
};
