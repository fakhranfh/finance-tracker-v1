<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('record_imports', function (Blueprint $table): void {
            $table->json('progress')->nullable()->after('choices');
        });
    }

    public function down(): void
    {
        Schema::table('record_imports', function (Blueprint $table): void {
            $table->dropColumn('progress');
        });
    }
};
