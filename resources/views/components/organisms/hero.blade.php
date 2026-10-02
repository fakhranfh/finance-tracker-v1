<section class="bg-background">
    <div class="mx-auto flex max-w-6xl flex-col items-center px-4 py-20 text-center">
        <span class="rounded-full bg-primary-container px-3 py-1 font-body text-label-md font-medium text-on-primary-container">
            {{ __('landing.hero.eyebrow') }}
        </span>

        <h1 class="mt-6 max-w-3xl font-heading text-headline-lg font-bold text-on-background sm:text-5xl">
            {{ __('landing.hero.title') }}
        </h1>

        <p class="mt-4 max-w-2xl font-body text-body-lg text-on-surface-variant">
            {{ __('landing.hero.subtitle') }}
        </p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <x-atoms.button :href="auth()->check() ? route('dashboard') : (Route::has('register') ? route('register') : '#')">
                {{ __('landing.hero.cta_primary') }}
            </x-atoms.button>
            <x-atoms.button href="https://laravel.com/docs" variant="secondary">
                {{ __('landing.hero.cta_secondary') }}
            </x-atoms.button>
        </div>
    </div>
</section>
