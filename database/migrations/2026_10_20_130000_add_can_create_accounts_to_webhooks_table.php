<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table): void {
            $table->boolean('can_create_accounts')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table): void {
            $table->dropColumn('can_create_accounts');
        });
    }
};
