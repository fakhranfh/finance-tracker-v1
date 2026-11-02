<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cycle_type_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedBigInteger('price');
            $table->date('bought_at');
            $table->date('finished_at')->nullable();
            $table->timestamps();

            $table->index(['cycle_type_id', 'bought_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_entries');
    }
};
