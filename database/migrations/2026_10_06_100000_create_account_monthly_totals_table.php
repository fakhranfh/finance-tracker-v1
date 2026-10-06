<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_monthly_totals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            // First day of the month in UTC, so any user timezone can map onto whole months.
            $table->date('month');
            $table->decimal('income', 14, 2)->default(0);
            $table->decimal('expense', 14, 2)->default(0);
            $table->decimal('transfer_in', 14, 2)->default(0);
            $table->decimal('transfer_out', 14, 2)->default(0);
            $table->unsignedInteger('record_count')->default(0);

            $table->unique(['account_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_monthly_totals');
    }
};
