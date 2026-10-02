<?php

namespace App\Traits\User;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the users list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample users backed by real database records.
     *
     * @return Collection<int, User>
     */
    protected function generateSampleUsers(int $count = 10): Collection
    {
        return User::factory()->count($count)->create();
    }
}
