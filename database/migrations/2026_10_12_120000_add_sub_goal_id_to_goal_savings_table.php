<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goal_savings', function (Blueprint $table): void {
            $table->foreignId('sub_goal_id')->nullable()->after('goal_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('goal_savings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sub_goal_id');
        });
    }
};
