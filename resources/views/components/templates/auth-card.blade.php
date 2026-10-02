@props([
    'title',
    'subtitle',
])

<p class="mb-6 text-center font-heading text-headline-md font-bold text-on-surface">{{ config('app.name') }}</p>

<div class="rounded-lg border border-outline-variant bg-surface p-8 shadow-sm">
    <h1 class="font-heading text-headline-sm font-bold text-on-surface">{{ $title }}</h1>
    <p class="mt-1 font-body text-body-md text-on-surface-variant">{{ $subtitle }}</p>

    <div class="mt-8">
        {{ $slot }}
    </div>
</div>
