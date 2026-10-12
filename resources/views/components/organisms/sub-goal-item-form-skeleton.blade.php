@props(['class' => ''])

<div {{ $attributes->merge(['class' => "space-y-5 {$class}"]) }}>
    @foreach (range(1, 4) as $field)
        <div class="space-y-1.5">
            <x-atoms.skeleton class="h-4 w-24" />
            <x-atoms.skeleton class="h-10 w-full" />
        </div>
    @endforeach
    <div class="flex items-center gap-2">
        <x-atoms.skeleton class="h-4 w-4" />
        <x-atoms.skeleton class="h-4 w-32" />
    </div>
    <div class="space-y-1.5">
        <x-atoms.skeleton class="h-4 w-24" />
        <x-atoms.skeleton class="h-10 w-full" />
    </div>
    <x-atoms.skeleton class="h-11 w-32" />
</div>
