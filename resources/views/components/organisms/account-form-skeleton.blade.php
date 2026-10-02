@props(['class' => ''])

<div {{ $attributes->merge(['class' => "space-y-5 {$class}"]) }}>
    <x-atoms.skeleton class="h-10 w-full" />
    <x-atoms.skeleton class="h-10 w-full" />
    <x-atoms.skeleton class="h-11 w-32" />
</div>
