@props([
    'name',
    'id' => null,
])

<input
    type="radio"
    name="{{ $name }}"
    id="{{ $id ?? $name }}"
    {{ $attributes->merge([
        'class' => 'h-4 w-4 cursor-pointer border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
    ]) }}
/>
