<?php

namespace App\Traits\Debt;

use App\Models\Debt;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the debts list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample debts backed by real database records.
     *
     * @return Collection<int, Debt>
     */
    protected function generateSampleDebts(int $count = 10): Collection
    {
        return Debt::factory()->count($count)->create();
    }
}
