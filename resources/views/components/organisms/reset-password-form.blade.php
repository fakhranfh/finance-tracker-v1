@props(['token', 'email' => null])

<div id="reset-password-form-wrapper">
    <x-organisms.reset-password-form-skeleton id="reset-password-form-skeleton" class="hidden" />

    <form id="reset-password-form" class="space-y-5" novalidate data-token="{{ $token }}">
        @csrf

        <x-molecules.form-group name="email" type="email" :label="__('auth.reset_password.email')" :value="$email" autocomplete="email" required />
        <x-molecules.form-group name="password" type="password" :label="__('auth.reset_password.password')" autocomplete="new-password" required />
        <x-molecules.form-group name="password_confirmation" type="password" :label="__('auth.reset_password.password_confirmation')" autocomplete="new-password" required />

        <x-atoms.button type="submit" class="w-full">
            {{ __('auth.reset_password.submit') }}
        </x-atoms.button>

        @if (app()->isLocal())
            <div class="flex items-center justify-center gap-3">
                <x-atoms.button type="button" variant="secondary" data-dev-autofill>Autofill</x-atoms.button>
            </div>
        @endif
    </form>
</div>
