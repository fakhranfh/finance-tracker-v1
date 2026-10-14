<?php

namespace App\Http\Requests\Goal;

use App\Models\Goal;
use App\Models\GoalSaving;
use App\Models\SubGoal;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared sub goal rules of the store and update goal requests: the sub goals sent along with a goal
 * must add up to the goal's target amount and saved amount.
 */
trait ValidatesSubGoals
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function subGoalRules(?Goal $goal): array
    {
        return [
            'sub_goals' => ['sometimes', 'array', 'max:50'],
            'sub_goals.*.id' => $goal
                ? ['nullable', 'integer', Rule::exists('sub_goals', 'id')->where('goal_id', $goal->id)]
                : ['prohibited'],
            'sub_goals.*.name' => ['required', 'string', 'max:255'],
            'sub_goals.*.target_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'sub_goals.*.saved_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'sub_goals.*.desired_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:desired_date'],
            'sub_goals.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function subGoalMessages(): array
    {
        return [
            'sub_goals.*.desired_date.before_or_equal' => __('sub-goals.validation.desired_date_max', ['date' => $this->input('desired_date')]),
        ];
    }

    /**
     * Once the other rules passed: the sub goal targets and saved amounts must add up to those of the goal,
     * and a sub goal that already has savings can't be removed.
     */
    protected function validateSubGoalTotals(Validator $validator, ?Goal $goal): void
    {
        $items = $this->input('sub_goals');

        if (! is_array($items) || $validator->errors()->isNotEmpty()) {
            return;
        }

        $existing = $goal ? $goal->subGoals()->get()->keyBy('id') : collect();
        $keptIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $removed = $existing->keys()->diff($keptIds);

        if ($goal && $removed->isNotEmpty() && ($existing->only($removed->all())->sum(fn (SubGoal $subGoal) => (float) $subGoal->saved_amount) > 0 || $goal->savings()->where('type', GoalSaving::TYPE_SAVING)->whereIn('sub_goal_id', $removed)->exists())) {
            $validator->errors()->add('sub_goals', __('sub-goals.validation.remove_with_savings'));

            return;
        }

        if ($items === []) {
            return;
        }

        $targetTotal = round(collect($items)->sum(fn (array $item) => (float) $item['target_amount']), 2);
        $savedTotal = $this->subGoalSavedTotal($items, $existing);

        if ($targetTotal !== round((float) $this->input('target_amount'), 2)) {
            $validator->errors()->add('sub_goals', __('sub-goals.validation.target_total', ['total' => $this->rupiah($targetTotal), 'target' => $this->rupiah((float) $this->input('target_amount'))]));
        } elseif ($savedTotal !== round((float) $this->input('saved_amount'), 2)) {
            $validator->errors()->add('sub_goals', __('sub-goals.validation.saved_total', ['total' => $this->rupiah($savedTotal), 'saved' => $this->rupiah((float) $this->input('saved_amount'))]));
        }
    }

    /**
     * The saved amounts of the given sub goals added up; a sub goal sent without one keeps what it has.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  \Illuminate\Support\Collection<int, SubGoal>  $existing  keyed by id
     */
    protected function subGoalSavedTotal(array $items, $existing): float
    {
        return round(collect($items)->sum(function (array $item) use ($existing): float {
            $current = ($item['id'] ?? null) && $existing->has((int) $item['id']) ? (float) $existing[(int) $item['id']]->saved_amount : 0.0;

            return (float) ($item['saved_amount'] ?? $current);
        }), 2);
    }

    private function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
