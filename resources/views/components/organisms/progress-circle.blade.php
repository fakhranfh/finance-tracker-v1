@props(['class' => ''])

<div
    {{ $attributes->merge(['class' => "flex flex-col items-center gap-4 rounded-lg border border-outline-variant bg-surface p-8 text-center {$class}"]) }}
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="100"
    aria-valuenow="0"
    aria-live="polite"
    data-progress
>
    <div class="relative h-32 w-32">
        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" aria-hidden="true">
            <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-surface-container-high" />
            <circle
                cx="60"
                cy="60"
                r="52"
                fill="none"
                stroke-width="10"
                stroke-linecap="round"
                pathLength="100"
                stroke-dasharray="100"
                stroke-dashoffset="100"
                class="stroke-primary transition-[stroke-dashoffset] duration-300 motion-reduce:transition-none"
                data-progress-ring
            />
        </svg>
        <span class="absolute inset-0 flex items-center justify-center font-heading text-headline-sm font-bold text-on-surface" data-progress-value>0%</span>
    </div>

    <div class="space-y-1">
        <h2 class="font-heading text-title-md font-semibold text-on-surface" data-progress-title></h2>
        <p class="max-w-md font-body text-body-md text-on-surface-variant" data-progress-description></p>
        <p class="font-body text-body-sm text-outline" data-progress-detail></p>
    </div>
</div>
