<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->foreignId('debt_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->foreignId('sub_debt_id')->nullable()->after('debt_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sub_debt_id');
            $table->dropConstrainedForeignId('debt_id');
        });
    }
};
