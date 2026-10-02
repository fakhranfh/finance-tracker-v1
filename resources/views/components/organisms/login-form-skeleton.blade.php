@props(['class' => ''])

<div {{ $attributes->merge(['class' => "space-y-5 {$class}"]) }}>
    <x-atoms.skeleton class="h-10 w-full" />
    <x-atoms.skeleton class="h-10 w-full" />
    <x-atoms.skeleton class="h-5 w-1/2" />
    <x-atoms.skeleton class="h-11 w-full" />
</div>
