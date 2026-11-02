<?php

namespace App\Http\Requests\CycleEntry;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FinishCycleEntryRequest extends FormRequest
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
            'finished_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->route('entry')->bought_at],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['finished_at.after_or_equal' => __('cycles.js.error_finish_before_buy')];
    }
}
