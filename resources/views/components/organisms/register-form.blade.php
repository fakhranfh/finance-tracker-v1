<div id="register-form-wrapper">
    <x-organisms.register-form-skeleton id="register-form-skeleton" class="hidden" />

    <form id="register-form" class="space-y-5" novalidate>
        @csrf

        <x-molecules.form-group name="name" type="text" :label="__('auth.register.name')" autocomplete="name" required />
        <x-molecules.form-group name="email" type="email" :label="__('auth.register.email')" autocomplete="email" required />
        <x-molecules.form-group name="password" type="password" :label="__('auth.register.password')" autocomplete="new-password" required />
        <x-molecules.form-group name="password_confirmation" type="password" :label="__('auth.register.password_confirmation')" autocomplete="new-password" required />

        <x-atoms.button type="submit" class="w-full">
            {{ __('auth.register.submit') }}
        </x-atoms.button>

        @if (app()->isLocal())
            <div class="flex items-center justify-center gap-3">
                <x-atoms.button type="button" variant="secondary" data-dev-autofill>Autofill</x-atoms.button>
            </div>
        @endif

        <p class="text-center font-body text-body-sm text-on-surface-variant">
            {{ __('auth.register.have_account') }}
            <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">
                {{ __('auth.register.login_link') }}
            </a>
        </p>
    </form>
</div>
