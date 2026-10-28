@props([
    'name',
    'label',
    'type' => 'text',
])

<div class="space-y-1.5">
    <x-atoms.label :for="$name">{{ $label }}</x-atoms.label>
    @if ($type === 'password')
        <div class="relative">
            <x-atoms.input :type="$type" :name="$name" :id="$name" {{ $attributes->merge(['class' => 'pr-11']) }} />
            <button
                type="button"
                data-password-toggle
                class="absolute inset-y-0 right-0 flex items-center px-3 text-outline hover:text-on-surface focus:outline-none focus-visible:text-primary"
                aria-label="{{ __('common.password.show') }}"
                data-label-show="{{ __('common.password.show') }}"
                data-label-hide="{{ __('common.password.hide') }}"
                aria-pressed="false"
            >
                <svg data-icon-show class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg data-icon-hide class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-6.5 0-10-7-10-7a18.5 18.5 0 0 1 4.06-5.06M9.9 4.24A9.12 9.12 0 0 1 12 5c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                    <path d="M2 2l20 20"></path>
                </svg>
            </button>
        </div>
    @else
        <x-atoms.input :type="$type" :name="$name" :id="$name" {{ $attributes }} />
    @endif
    <x-atoms.form-error :for="$name" />
</div>
