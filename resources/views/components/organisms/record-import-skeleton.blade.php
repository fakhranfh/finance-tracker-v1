@props(['class' => ''])

<div {{ $attributes->merge(['class' => "space-y-5 rounded-lg border border-outline-variant bg-surface p-6 {$class}"]) }}>
    <x-atoms.skeleton class="h-6 w-48" />
    @for ($i = 0; $i < 4; $i++)
        <div class="space-y-1.5">
            <x-atoms.skeleton class="h-4 w-24" />
            <x-atoms.skeleton class="h-10 w-full" />
        </div>
    @endfor
    <x-atoms.skeleton class="h-11 w-32" />
</div>
