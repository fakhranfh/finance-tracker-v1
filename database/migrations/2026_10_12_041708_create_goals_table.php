<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('target_amount', 14, 2);
            $table->decimal('saved_amount', 14, 2)->default(0);
            $table->date('desired_date');
            $table->string('color', 7)->default('#2563EB');
            $table->string('icon', 30)->default('flag');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
