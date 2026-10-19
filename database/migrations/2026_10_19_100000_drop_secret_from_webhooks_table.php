<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Requests are now signed with the webhook token, so the separate secret is gone.
        Schema::table('webhooks', function (Blueprint $table): void {
            $table->dropColumn('secret');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table): void {
            $table->text('secret')->nullable()->after('token');
        });
    }
};
