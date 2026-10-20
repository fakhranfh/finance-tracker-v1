@props(['class' => ''])

<div {{ $attributes->merge(['class' => "space-y-5 {$class}"]) }}>
    <div class="space-y-1.5">
        <x-atoms.skeleton class="h-4 w-24" />
        <x-atoms.skeleton class="h-10 w-full" />
    </div>
    <div class="space-y-1.5">
        <x-atoms.skeleton class="h-4 w-24" />
        <x-atoms.skeleton class="h-10 w-full" />
    </div>
    <div class="space-y-1.5">
        <x-atoms.skeleton class="h-4 w-24" />
        <x-atoms.skeleton class="h-10 w-full" />
    </div>
    <div class="space-y-2">
        <x-atoms.skeleton class="h-4 w-24" />
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <x-atoms.skeleton class="h-4 w-4" />
                <x-atoms.skeleton class="h-4 w-24" />
            </div>
            <div class="flex items-center gap-2">
                <x-atoms.skeleton class="h-4 w-4" />
                <x-atoms.skeleton class="h-4 w-24" />
            </div>
        </div>
    </div>
    <div class="space-y-1.5">
        <x-atoms.skeleton class="h-4 w-24" />
        <x-atoms.skeleton class="h-20 w-full" />
    </div>
    <div class="space-y-1.5">
        <x-atoms.skeleton class="h-4 w-24" />
        <x-atoms.skeleton class="h-6 w-full" />
    </div>
    <div class="space-y-1.5">
        <div class="flex items-center gap-2">
            <x-atoms.skeleton class="h-4 w-4" />
            <x-atoms.skeleton class="h-4 w-40" />
        </div>
        <x-atoms.skeleton class="h-4 w-full" />
    </div>
    <div class="flex items-center gap-2">
        <x-atoms.skeleton class="h-4 w-4" />
        <x-atoms.skeleton class="h-4 w-32" />
    </div>
    <x-atoms.skeleton class="h-11 w-32" />
</div>
