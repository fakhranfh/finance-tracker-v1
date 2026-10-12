@props(['class' => ''])

<div {{ $attributes->merge(['class' => "space-y-5 {$class}"]) }}>
    <x-atoms.skeleton class="h-6 w-full" />
    <x-atoms.skeleton class="h-6 w-full" />
    <x-atoms.skeleton class="h-6 w-full" />
    <x-atoms.skeleton class="h-6 w-full" />
    <x-atoms.skeleton class="h-6 w-full" />
    <x-atoms.skeleton class="h-6 w-full" />
    <x-atoms.skeleton class="h-6 w-full" />
    <x-atoms.skeleton class="h-6 w-full" />
</div>
