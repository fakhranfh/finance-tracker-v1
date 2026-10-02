<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balance_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_record_id');
            $table->decimal('balance', 14, 2);
            $table->timestamp('taken_at')->useCurrent();

            $table->unique(['account_id', 'last_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balance_snapshots');
    }
};
