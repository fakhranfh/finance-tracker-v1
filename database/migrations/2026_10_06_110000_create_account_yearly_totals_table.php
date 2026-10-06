<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_yearly_totals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            // Calendar year in UTC, matching the monthly totals.
            $table->unsignedSmallInteger('year');
            $table->decimal('income', 14, 2)->default(0);
            $table->decimal('expense', 14, 2)->default(0);
            $table->decimal('transfer_in', 14, 2)->default(0);
            $table->decimal('transfer_out', 14, 2)->default(0);
            $table->unsignedInteger('record_count')->default(0);

            $table->unique(['account_id', 'year']);
        });

        // Backfill from the existing monthly totals.
        $years = [];
        DB::table('account_monthly_totals')
            ->orderBy('month')
            ->get(['account_id', 'month', 'income', 'expense', 'transfer_in', 'transfer_out', 'record_count'])
            ->each(function ($row) use (&$years): void {
                $key = $row->account_id.'-'.substr((string) $row->month, 0, 4);
                $years[$key] ??= [
                    'account_id' => $row->account_id, 'year' => (int) substr((string) $row->month, 0, 4),
                    'income' => 0, 'expense' => 0, 'transfer_in' => 0, 'transfer_out' => 0, 'record_count' => 0,
                ];
                foreach (['income', 'expense', 'transfer_in', 'transfer_out'] as $column) {
                    $years[$key][$column] = round($years[$key][$column] + (float) $row->{$column}, 2);
                }
                $years[$key]['record_count'] += $row->record_count;
            });

        foreach (array_chunk(array_values($years), 500) as $chunk) {
            DB::table('account_yearly_totals')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_yearly_totals');
    }
};
