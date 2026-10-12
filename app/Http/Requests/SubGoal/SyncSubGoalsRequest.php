<?php

namespace App\Http\Requests\SubGoal;

use App\Http\Requests\Goal\ValidatesSubGoals;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Replaces the sub goals of a goal. They are checked against the goal's stored target amount,
 * saved amount and desired date, which this request borrows from the goal in the route.
 */
class SyncSubGoalsRequest extends FormRequest
{
    use ValidatesSubGoals;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $goal = $this->route('goal');

        $this->merge([
            'target_amount' => $goal->target_amount,
            'saved_amount' => $goal->saved_amount,
            'desired_date' => $goal->desired_date,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->subGoalRules($this->route('goal')),
            'sub_goals' => ['present', 'array', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->subGoalMessages();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateSubGoalTotals($validator, $this->route('goal'))];
    }
}
