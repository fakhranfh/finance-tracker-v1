<section class="bg-primary">
    <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 px-4 py-16 text-center">
        <h2 class="font-heading text-headline-md font-bold text-on-primary">
            {{ __('landing.cta.title') }}
        </h2>
        <p class="max-w-xl font-body text-body-lg text-on-primary/90">
            {{ __('landing.cta.subtitle') }}
        </p>
        <x-atoms.button
            :href="auth()->check() ? route('dashboard') : (Route::has('register') ? route('register') : '#')"
            variant="inverse"
        >
            {{ __('landing.cta.button') }}
        </x-atoms.button>
    </div>
</section>
