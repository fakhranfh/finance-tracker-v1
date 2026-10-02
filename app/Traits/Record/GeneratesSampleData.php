<?php

namespace App\Traits\Record;

use App\Models\Record;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the records list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample records backed by real database records.
     *
     * @return Collection<int, Record>
     */
    protected function generateSampleRecords(int $count = 10): Collection
    {
        return Record::factory()->count($count)->create();
    }
}
