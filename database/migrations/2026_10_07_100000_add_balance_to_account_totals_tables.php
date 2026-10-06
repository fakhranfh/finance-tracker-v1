<?php

use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['account_monthly_totals', 'account_yearly_totals'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->decimal('net', 14, 2)->default(0);
                $table->decimal('balance', 14, 2)->default(0);
            });
        }

        // Existing rows have no net or balance yet, so rebuild them from the records.
        $summaries = app(AccountSummaryRepositoryInterface::class);
        $accountIds = DB::table('accounts')->pluck('id')->all();

        $summaries->rebuild($accountIds);
        $summaries->rebuildYears($accountIds);
    }

    public function down(): void
    {
        foreach (['account_monthly_totals', 'account_yearly_totals'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn(['net', 'balance']);
            });
        }
    }
};
