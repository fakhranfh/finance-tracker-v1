<?php

namespace App\Traits\Receivable;

use App\Models\Receivable;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the receivables list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample receivables backed by real database records.
     *
     * @return Collection<int, Receivable>
     */
    protected function generateSampleReceivables(int $count = 10): Collection
    {
        return Receivable::factory()->count($count)->create();
    }
}
