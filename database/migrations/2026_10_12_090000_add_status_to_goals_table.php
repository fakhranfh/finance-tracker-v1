<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table): void {
            $table->string('status', 20)->default('active')->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
