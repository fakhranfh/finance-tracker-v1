<?php

namespace App\Traits\SubDebt;

use App\Models\Debt;
use App\Models\SubDebt;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the sub debts list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample sub debts for the debt, backed by real database records; the debt's totals follow them.
     *
     * @return Collection<int, SubDebt>
     */
    protected function generateSampleSubDebts(Debt $debt, int $count = 10): Collection
    {
        $subDebts = SubDebt::factory()->for($debt)->count($count)->create();

        $this->subDebtManagementService->refreshTotals($debt);

        return $subDebts;
    }
}
