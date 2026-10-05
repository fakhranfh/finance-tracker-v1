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
     * @param  array<int, int>  $accountIds  Accounts to spread the records across; empty creates new accounts.
     * @return Collection<int, Record>
     */
    protected function generateSampleRecords(int $count = 10, array $accountIds = []): Collection
    {
        if ($accountIds === []) {
            return Record::factory()->count($count)->create();
        }

        return Record::factory()->count($count)->state(fn () => [
            'account_id' => fake()->randomElement($accountIds),
        ])->create();
    }
}
