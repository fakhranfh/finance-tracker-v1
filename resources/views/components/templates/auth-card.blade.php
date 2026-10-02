@props([
    'title',
    'subtitle',
    'backHref' => null,
])

<x-atoms.back-link :href="$backHref ?? route('landing')">
    {{ __('auth.back_to_home') }}
</x-atoms.back-link>

<div class="rounded-lg border border-outline-variant bg-surface p-8 shadow-sm">
    <h1 class="font-heading text-headline-sm font-bold text-on-surface">{{ $title }}</h1>
    <p class="mt-1 font-body text-body-md text-on-surface-variant">{{ $subtitle }}</p>

    <div class="mt-8">
        {{ $slot }}
    </div>
</div>
