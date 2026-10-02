<?php

namespace App\Traits\Account;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the accounts list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample accounts backed by real database records.
     *
     * @return Collection<int, Account>
     */
    protected function generateSampleAccounts(int $count = 10): Collection
    {
        return Account::factory()->count($count)->create();
    }
}
