@props(['class' => ''])

<div {{ $attributes->merge(['class' => "space-y-5 {$class}"]) }}>
    <div class="space-y-1.5">
        <x-atoms.skeleton class="h-4 w-24" />
        <x-atoms.skeleton class="h-10 w-full" />
    </div>
    <x-atoms.skeleton class="h-11 w-32" />
</div>
