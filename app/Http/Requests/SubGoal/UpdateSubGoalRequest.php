<?php

namespace App\Http\Requests\SubGoal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A single sub goal takes its own details, its saved amount and the done flag. Its target amount is not among them:
 * the sub goals must add up to the goal's target, so amounts change together through the goal's sub goals.
 */
class UpdateSubGoalRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'desired_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:'.$this->route('goal')->desired_date],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'saved_amount' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999999.99'],
            'is_done' => ['required_without_all:name,desired_date,note,saved_amount', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'desired_date.before_or_equal' => __('sub-goals.validation.desired_date_max', ['date' => $this->route('goal')->desired_date]),
        ];
    }
}
