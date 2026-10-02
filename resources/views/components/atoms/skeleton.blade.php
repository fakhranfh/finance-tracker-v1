@props(['class' => 'h-4 w-full'])

<div {{ $attributes->merge(['class' => "animate-pulse rounded-md bg-surface-container-high {$class}"]) }}></div>
