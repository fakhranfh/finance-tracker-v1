@props([
    'name',
    'id' => null,
])

<select
    name="{{ $name }}"
    id="{{ $id ?? $name }}"
    {{ $attributes->merge([
        'class' => 'w-full rounded-md border border-outline-variant bg-surface px-4 py-2.5 font-body text-body-md text-on-surface focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
    ]) }}
>
    {{ $slot }}
</select>
