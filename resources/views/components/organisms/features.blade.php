<section id="features" class="bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-20">
        <div class="mx-auto max-w-2xl text-center">
            <span class="font-body text-label-md font-semibold uppercase tracking-wider text-primary">
                {{ __('landing.features.eyebrow') }}
            </span>
            <h2 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('landing.features.title') }}
            </h2>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (__('landing.features.items') as $item)
                <div class="rounded-lg border border-outline-variant bg-surface-container-lowest p-6">
                    <h3 class="font-heading text-headline-sm font-semibold text-on-surface">
                        {{ $item['title'] }}
                    </h3>
                    <p class="mt-2 font-body text-body-sm text-on-surface-variant">
                        {{ $item['description'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
