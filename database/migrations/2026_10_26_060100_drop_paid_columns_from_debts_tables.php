<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What has been paid is the sum of the records linked to a debt or sub debt, so it is no longer stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            $table->dropColumn(['paid_amount', 'is_paid']);
        });

        Schema::table('sub_debts', function (Blueprint $table): void {
            $table->dropColumn(['paid_amount', 'is_paid']);
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->boolean('is_paid')->default(false);
        });

        Schema::table('sub_debts', function (Blueprint $table): void {
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->boolean('is_paid')->default(false);
        });
    }
};
