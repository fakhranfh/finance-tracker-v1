@props(['class' => ''])

<div {{ $attributes->merge(['class' => "grid grid-cols-3 gap-3 sm:grid-cols-2 lg:grid-cols-5 {$class}"]) }}>
    @for ($i = 0; $i < 15; $i++)
        <x-atoms.skeleton class="h-16 w-full rounded-lg" />
    @endfor
</div>
