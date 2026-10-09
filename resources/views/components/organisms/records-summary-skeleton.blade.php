@props(['class' => ''])

<div {{ $attributes->merge(['class' => "grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 {$class}"]) }}>
    @for ($i = 0; $i < 4; $i++)
        <x-atoms.skeleton class="h-24 rounded-lg" />
    @endfor
</div>
