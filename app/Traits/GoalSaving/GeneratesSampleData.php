<?php

namespace App\Traits\GoalSaving;

use App\Models\Goal;
use App\Models\GoalSaving;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for a goal's saving history.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Add sample savings to a goal, each one also raising its saved amount.
     *
     * @return Collection<int, GoalSaving>
     */
    protected function generateSampleSavings(Goal $goal, int $count = 5): Collection
    {
        $subGoalIds = $goal->subGoals()->pluck('id');

        return collect(range(1, $count))->map(
            fn (): GoalSaving => $this->goalSavingService->addSaving($goal, fake()->numberBetween(1, 100) * 10000, $subGoalIds->isEmpty() ? null : $subGoalIds->random())
        );
    }
}
