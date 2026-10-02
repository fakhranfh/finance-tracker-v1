<div id="forgot-password-form-wrapper">
    <x-organisms.forgot-password-form-skeleton id="forgot-password-form-skeleton" class="hidden" />

    <form id="forgot-password-form" class="space-y-5" novalidate>
        @csrf

        <div data-form-success class="hidden rounded-md border border-success/20 bg-success/10 px-4 py-3 font-body text-body-sm text-success"></div>

        <x-molecules.form-group name="email" type="email" :label="__('auth.forgot_password.email')" autocomplete="email" required />

        <x-atoms.button type="submit" class="w-full">
            {{ __('auth.forgot_password.submit') }}
        </x-atoms.button>

        @if (app()->isLocal())
            <div class="flex items-center justify-center gap-3">
                <x-atoms.button type="button" variant="secondary" data-dev-autofill>Autofill</x-atoms.button>
            </div>
        @endif

        <p class="text-center font-body text-body-sm text-on-surface-variant">
            <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">
                {{ __('auth.forgot_password.back_to_login') }}
            </a>
        </p>
    </form>
</div>
