<?php

namespace App\Http\Requests\Goal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoalSavingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'gt:0', 'max:999999999999'],
            'sub_goal_id' => [Rule::requiredIf(fn () => $this->route('goal')->subGoals()->exists()), 'nullable', 'integer', Rule::exists('sub_goals', 'id')->where('goal_id', $this->route('goal')->id)],
        ];
    }
}
