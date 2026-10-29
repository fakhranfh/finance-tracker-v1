@extends('layouts.dashboard')

@section('title', __('terminal.title'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('terminal.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">{{ __('terminal.subtitle') }}</p>
        </div>

        <x-atoms.button type="button" variant="secondary" class="hidden" data-terminal-lock>
            {{ __('terminal.console.lock') }}
        </x-atoms.button>
    </div>

    <div class="w-full space-y-8 rounded-lg border border-outline-variant bg-surface p-6">
        <x-organisms.terminal-skeleton id="terminal-skeleton" />

        <form id="terminal-unlock" class="hidden space-y-5" novalidate data-wrong-password="{{ __('terminal.errors.wrong_password') }}" data-failed="{{ __('terminal.errors.failed') }}">
            @csrf

            <h2 class="font-heading text-title-md font-semibold text-on-surface">{{ __('terminal.unlock.heading') }}</h2>

            <x-molecules.form-group name="terminal_password" type="password" :label="__('terminal.unlock.password')" autocomplete="off" required autofocus />

            <div class="flex flex-wrap items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('terminal.unlock.submit') }}
                </x-atoms.button>
                <x-atoms.button type="button" variant="secondary" data-terminal-forgot>
                    {{ __('terminal.forgot.link') }}
                </x-atoms.button>
            </div>

            <p data-forgot-notice class="hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success" role="status"></p>
        </form>

        <div id="terminal-console" class="hidden space-y-4" data-exit-code="{{ __('terminal.console.exit_code', ['code' => ':code']) }}" data-failed="{{ __('terminal.errors.failed') }}">
            <div data-terminal-output class="h-96 overflow-y-auto rounded-md bg-inverse-surface p-4 font-mono text-body-sm text-inverse-on-surface" role="log" aria-live="polite">
                <p class="whitespace-pre-wrap text-outline-variant">{{ __('terminal.console.welcome') }}</p>
            </div>

            <form id="terminal-form" class="space-y-1.5" novalidate>
                @csrf

                <x-atoms.label for="command">{{ __('terminal.console.command') }}</x-atoms.label>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-body-md text-on-surface-variant">$</span>
                    <x-atoms.input name="command" id="command" class="min-w-0 flex-1 font-mono" autocomplete="off" autocapitalize="off" spellcheck="false" required :placeholder="__('terminal.console.placeholder')" />
                    <x-atoms.button type="submit">
                        {{ __('terminal.console.run') }}
                    </x-atoms.button>
                </div>
                <x-atoms.form-error for="command" />
            </form>
        </div>

        <form id="terminal-password-form" class="hidden space-y-5" novalidate data-failed="{{ __('terminal.errors.failed') }}" data-setup-heading="{{ __('terminal.password.setup_heading') }}" data-reset-heading="{{ __('terminal.password.reset_heading') }}" data-change-heading="{{ __('terminal.password.change_heading') }}" data-saved="{{ __('terminal.password.saved') }}">
            @csrf

            <div>
                <h2 data-password-heading class="font-heading text-title-md font-semibold text-on-surface"></h2>
                <p data-password-hint class="mt-1 hidden font-body text-body-sm text-on-surface-variant">{{ __('terminal.password.setup_hint') }}</p>
            </div>

            <div data-current-password-group>
                <x-molecules.form-group name="current_password" type="password" :label="__('terminal.password.current')" autocomplete="current-password" />
            </div>
            <x-molecules.form-group name="password" type="password" :label="__('terminal.password.new')" autocomplete="new-password" required />
            <p class="-mt-3.5 font-body text-body-sm text-on-surface-variant">{{ __('users.fields.password_hint') }}</p>
            <x-molecules.form-group name="password_confirmation" type="password" :label="__('terminal.password.confirmation')" autocomplete="new-password" required />

            <x-atoms.button type="submit">
                {{ __('terminal.password.save') }}
            </x-atoms.button>
        </form>

        <p data-password-saved class="mt-4 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success" role="status"></p>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/terminal/index.js')
@endpush
