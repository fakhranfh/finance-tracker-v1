@props(['class' => ''])

<div {{ $attributes->merge(['class' => "grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5 {$class}"]) }}>
    <x-atoms.skeleton class="h-16 w-full" />
    <x-atoms.skeleton class="h-16 w-full" />
    <x-atoms.skeleton class="h-16 w-full" />
    <x-atoms.skeleton class="h-16 w-full" />
    <x-atoms.skeleton class="h-16 w-full" />
</div>
