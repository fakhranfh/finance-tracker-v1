<?php

namespace App\Traits\Goal;

use App\Models\Goal;
use App\Services\SubGoalManagementService;
use Illuminate\Support\Collection;

/**
 * Dev-only sample-data generator for the goals list page.
 * Per .ai/rules/dev-autofill.md.
 */
trait GeneratesSampleData
{
    /**
     * Create sample goals backed by real database records, each split into sub goals
     * whose target and saved amounts add up to the goal's.
     *
     * @return Collection<int, Goal>
     */
    protected function generateSampleGoals(int $count = 10): Collection
    {
        return Goal::factory()->count($count)->create()->each(function (Goal $goal): void {
            app(SubGoalManagementService::class)->sync($goal, $this->sampleSubGoals($goal));
        });
    }

    /**
     * Two to four sub goals sharing the goal's target and saved amounts, in whole thousands, the last one taking the remainder.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sampleSubGoals(Goal $goal): array
    {
        $samples = collect(config('goal.sub_goal_samples'))->shuffle()->take(fake()->numberBetween(2, 4))->values();
        $targets = $this->splitInThousands((float) $goal->target_amount, $samples->count());
        $saved = $this->splitInThousands((float) $goal->saved_amount, $samples->count());

        return $samples->map(fn (array $sample, int $index): array => [
            'name' => $sample['name'],
            'target_amount' => $targets[$index],
            'saved_amount' => $saved[$index],
            'desired_date' => fake()->dateTimeBetween('today', max('today', $goal->desired_date))->format('Y-m-d'),
            'note' => $sample['note'],
        ])->all();
    }

    /**
     * @return array<int, float>
     */
    private function splitInThousands(float $total, int $parts): array
    {
        $shares = [];
        $left = $total;

        for ($i = 1; $i < $parts; $i++) {
            $share = floor($left / ($parts - $i + 1) * fake()->randomFloat(2, 0.5, 1.5) / 1000) * 1000;
            $share = min($share, $left);
            $shares[] = $share;
            $left -= $share;
        }

        $shares[] = $left;

        return $shares;
    }
}
