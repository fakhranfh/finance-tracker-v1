<footer class="border-t border-outline-variant bg-surface">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row">
        <div class="text-center sm:text-left">
            <p class="font-heading text-body-md font-semibold text-on-surface">{{ config('app.name') }}</p>
            <p class="font-body text-body-sm text-on-surface-variant">{{ __('landing.footer.tagline') }}</p>
        </div>

        <p class="font-body text-body-sm text-on-surface-variant">
            &copy; {{ now()->year }} {{ config('app.name') }}. {{ __('landing.footer.rights') }}
        </p>
    </div>
</footer>
