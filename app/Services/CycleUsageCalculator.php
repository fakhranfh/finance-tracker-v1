<?php

namespace App\Services;

use App\Models\CycleEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Turns the purchases of one item type into how long a product lasts and what that costs per month.
 */
class CycleUsageCalculator
{
    private const DAYS_PER_MONTH = 30;

    /**
     * The average days a product lasted (finished purchases only) and the monthly cost: the average price of
     * all purchases divided by that average, times 30. Both are null until one purchase has finished.
     *
     * @param  Collection<int, CycleEntry>  $entries
     * @return array{avg_days: int|null, monthly: int|null}
     */
    public function forEntries(Collection $entries): array
    {
        $finished = $entries->filter(fn (CycleEntry $entry) => $entry->finished_at !== null);

        if ($finished->isEmpty()) {
            return ['avg_days' => null, 'monthly' => null];
        }

        $avgDays = (float) $finished->avg(fn (CycleEntry $entry) => $this->days($entry));
        $avgPrice = (float) $entries->avg('price');

        return [
            'avg_days' => (int) round($avgDays),
            'monthly' => $avgDays > 0 ? (int) round($avgPrice / $avgDays * self::DAYS_PER_MONTH) : null,
        ];
    }

    private function days(CycleEntry $entry): int
    {
        $bought = CarbonImmutable::parse((string) $entry->bought_at)->startOfDay();
        $finished = CarbonImmutable::parse((string) $entry->finished_at)->startOfDay();

        return (int) round($bought->diffInDays($finished, true));
    }
}
