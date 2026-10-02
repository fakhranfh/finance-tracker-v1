@props([
    'name' => null,
    'id' => null,
])

<input
    type="checkbox"
    @if ($name) name="{{ $name }}" @endif
    id="{{ $id ?? $name }}"
    {{ $attributes->merge([
        'class' => 'h-4 w-4 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
    ]) }}
/>
