<?php

namespace App\Http\Requests\CycleType;

use App\Models\CycleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCycleTypeRequest extends FormRequest
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
        /** @var CycleType $type */
        $type = $this->route('type');

        return [
            'name' => [
                'required', 'string', 'max:40',
                Rule::unique('cycle_types', 'name')->where('category_id', $type->category_id)->ignore($type->id),
            ],
            'icon' => ['sometimes', 'string', Rule::in(array_keys(config('cycle.icons')))],
        ];
    }
}
