@extends('layouts.dashboard')

@section('title', __('profile.title'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('profile.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">{{ __('profile.subtitle') }}</p>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('users.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success" role="status"></div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        <x-organisms.user-form-skeleton id="profile-form-skeleton" />

        <form id="profile-form" class="hidden space-y-5" novalidate data-success-message="{{ __('profile.flash.updated') }}">
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('users.fields.name')" autocomplete="name" required autofocus />
            <x-molecules.form-group name="email" type="email" :label="__('users.fields.email')" autocomplete="email" required />
            <x-molecules.form-group name="current_password" type="password" :label="__('profile.fields.current_password')" autocomplete="current-password" />
            <x-molecules.form-group name="password" type="password" :label="__('profile.fields.new_password')" autocomplete="new-password" />
            <p class="-mt-3.5 font-body text-body-sm text-on-surface-variant">{{ __('users.fields.password_hint') }}</p>
            <x-molecules.form-group name="password_confirmation" type="password" :label="__('users.fields.password_confirmation')" autocomplete="new-password" />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('users.actions.save') }}
                </x-atoms.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/profile/edit.js')
@endpush
