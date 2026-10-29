<?php

namespace App\Http\Requests\Terminal;

use App\Services\TerminalService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ChangeTerminalPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                Rule::requiredIf(fn () => app(TerminalService::class)->isConfigured()),
                'nullable',
                'string',
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
