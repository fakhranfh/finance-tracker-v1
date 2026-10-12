<?php

namespace App\Traits\Goal;

use App\Models\Goal;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the goals list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample goals backed by real database records.
     *
     * @return Collection<int, Goal>
     */
    protected function generateSampleGoals(int $count = 10): Collection
    {
        return Goal::factory()->count($count)->create();
    }
}
