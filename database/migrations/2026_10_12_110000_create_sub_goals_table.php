<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('target_amount', 12, 2);
            $table->date('desired_date')->nullable();
            $table->boolean('is_done')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_goals');
    }
};
