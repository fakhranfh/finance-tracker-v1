<?php

namespace App\Http\Requests\CycleType;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCycleTypeRequest extends FormRequest
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
            'name' => [
                'required', 'string', 'max:40',
                Rule::unique('cycle_types', 'name')->where('category_id', $this->route('category')->id),
            ],
            'icon' => ['sometimes', 'string', Rule::in(array_keys(config('cycle.icons')))],
        ];
    }
}
