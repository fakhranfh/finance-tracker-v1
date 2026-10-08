<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->index(['account_id', 'recorded_at']);
            $table->index(['to_account_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropIndex(['account_id', 'recorded_at']);
            $table->dropIndex(['to_account_id', 'recorded_at']);
        });
    }
};
