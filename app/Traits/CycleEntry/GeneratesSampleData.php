<?php

namespace App\Traits\CycleEntry;

use App\Models\CycleEntry;
use App\Models\CycleType;
use Database\Factories\CycleEntryFactory;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the purchases list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample purchases for the item name: one product bought again and again, the newest still in use.
     *
     * @return Collection<int, CycleEntry>
     */
    protected function generateSampleEntries(CycleType $type, int $count = 6): Collection
    {
        return CycleEntryFactory::createChain($type, $count);
    }
}
