<?php

namespace App\Http\Requests\Category;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request. The type is locked once
     * records use the category, so their type can't drift from it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');
        $types = $category->records()->exists()
            ? [$category->type->value]
            : array_column(CategoryType::cases(), 'value');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where('type', $this->input('type'))->ignore($category),
            ],
            'type' => ['required', Rule::enum(CategoryType::class), Rule::in($types)],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', Rule::in(array_keys(config('category.icons')))],
        ];
    }
}
