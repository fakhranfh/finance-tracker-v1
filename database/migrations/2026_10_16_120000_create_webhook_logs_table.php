<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('status_code');
            $table->string('ip', 45)->nullable();
            $table->string('origin')->nullable();
            $table->string('message')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
