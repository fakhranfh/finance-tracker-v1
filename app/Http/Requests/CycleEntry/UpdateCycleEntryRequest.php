<?php

namespace App\Http\Requests\CycleEntry;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCycleEntryRequest extends FormRequest
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
        $finishedAt = $this->route('entry')->finished_at;

        return [
            'name' => ['required', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'bought_at' => ['required', 'date_format:Y-m-d', ...($finishedAt ? ['before_or_equal:'.$finishedAt] : [])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['bought_at.before_or_equal' => __('cycles.js.error_finish_before_buy')];
    }
}
