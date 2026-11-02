@props(['class' => ''])

<div {{ $attributes->merge(['class' => $class]) }}>
    <div class="space-y-4 rounded-lg border border-outline-variant bg-surface p-5">
        <x-atoms.skeleton class="h-7 w-56" />
        <x-atoms.skeleton class="h-px w-full" />
        <x-atoms.skeleton class="h-5 w-40" />
        <x-atoms.skeleton class="h-px w-full" />
        <x-atoms.skeleton class="h-5 w-52" />
    </div>

    <x-atoms.skeleton class="mt-4 h-16 w-full rounded-lg" />
</div>
