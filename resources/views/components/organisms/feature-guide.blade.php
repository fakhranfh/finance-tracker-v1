<div class="space-y-6">
    <div class="space-y-2">
        <h1 class="font-heading text-headline-md font-bold text-on-surface">
            {{ __('dashboard.welcome.greeting', ['name' => $name]) }}
        </h1>
        <p class="font-body text-body-lg text-on-surface-variant">{{ __('dashboard.welcome.intro') }}</p>
    </div>

    <h2 class="font-heading text-headline-sm font-semibold text-on-surface">{{ __('dashboard.welcome.features_title') }}</h2>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($features as $feature)
            <a
                href="{{ route($feature['route']) }}"
                class="flex gap-4 rounded-lg border border-outline-variant bg-surface p-4 transition-colors hover:bg-surface-container-low"
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary-container text-on-primary-container">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        {!! $icons[$feature['icon']] !!}
                    </svg>
                </span>
                <span class="min-w-0 space-y-1">
                    <span class="block font-body text-body-md font-semibold text-on-surface">{{ __($feature['label']) }}</span>
                    <span class="block font-body text-body-sm text-on-surface-variant">{{ __($feature['description']) }}</span>
                </span>
            </a>
        @endforeach
    </div>
</div>
