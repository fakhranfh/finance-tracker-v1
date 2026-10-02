<div id="login-form-wrapper">
    <x-organisms.login-form-skeleton id="login-form-skeleton" class="hidden" />

    <form id="login-form" class="space-y-5" novalidate>
        @csrf

        <x-molecules.form-group name="email" type="email" :label="__('auth.login.email')" autocomplete="email" required />
        <x-molecules.form-group name="password" type="password" :label="__('auth.login.password')" autocomplete="current-password" required />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 font-body text-body-sm text-on-surface-variant">
                <input type="checkbox" name="remember" class="rounded border-outline-variant text-primary focus:ring-primary/20" />
                {{ __('auth.login.remember') }}
            </label>

            <a href="{{ route('password.request') }}" class="font-body text-body-sm text-primary hover:underline">
                {{ __('auth.login.forgot_password') }}
            </a>
        </div>

        <x-atoms.button type="submit" class="w-full">
            {{ __('auth.login.submit') }}
        </x-atoms.button>

        @if (app()->isLocal())
            <div class="flex items-center justify-center gap-3">
                <x-atoms.button type="button" variant="secondary" data-dev-autofill>Autofill</x-atoms.button>
                <x-atoms.button type="button" variant="secondary" data-dev-auto-login>Auto login</x-atoms.button>
            </div>
        @endif

        <p class="text-center font-body text-body-sm text-on-surface-variant">
            {{ __('auth.login.no_account') }}
            <a href="{{ route('register') }}" class="font-medium text-primary hover:underline">
                {{ __('auth.login.register_link') }}
            </a>
        </p>
    </form>
</div>
