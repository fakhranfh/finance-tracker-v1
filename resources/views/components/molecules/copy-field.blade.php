@props([
    'name',
    'label',
    'copyLabel',
    'copiedLabel',
])

<div class="space-y-1.5">
    <x-atoms.label :for="$name">{{ $label }}</x-atoms.label>
    <div class="relative">
        <x-atoms.input type="text" :name="$name" :id="$name" readonly {{ $attributes->merge(['class' => 'pl-24 font-mono']) }} />
        <button
            type="button"
            class="absolute inset-y-0 left-0 cursor-pointer rounded-l-md px-4 font-body text-body-sm font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-primary/20"
            data-copy-target="{{ $name }}"
            data-copy-label="{{ $copyLabel }}"
            data-copied-label="{{ $copiedLabel }}"
        >
            {{ $copyLabel }}
        </button>
    </div>
    <x-atoms.form-error :for="$name" />
</div>
