<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_goals', function (Blueprint $table): void {
            $table->decimal('saved_amount', 14, 2)->default(0)->after('target_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sub_goals', function (Blueprint $table): void {
            $table->dropColumn('saved_amount');
        });
    }
};
