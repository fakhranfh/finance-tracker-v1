<?php

namespace App\Http\Requests\Goal;

use App\Enums\GoalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoalRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'saved_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'desired_date' => ['required', 'date_format:Y-m-d'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', Rule::in(array_keys(config('goal.icons')))],
            'status' => ['required', Rule::enum(GoalStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
