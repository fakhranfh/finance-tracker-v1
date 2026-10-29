@extends('layouts.dashboard')

@section('title', ($id ? __('users.edit') : __('users.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('users.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('users.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('users.edit') : __('users.create') }}
            </h1>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('users.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($id)
            <x-organisms.user-form-skeleton id="user-form-skeleton" />
        @endif

        <form
            id="user-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('users.flash.updated') : __('users.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('users.fields.name')" required autofocus />
            <x-molecules.form-group name="email" type="email" :label="__('users.fields.email')" required autocomplete="off" />

            <fieldset class="space-y-2">
                <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('users.fields.role') }}</legend>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    @foreach (\App\Enums\RoleName::cases() as $role)
                        <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                            <x-atoms.radio name="role" :id="'role-'.$role->value" value="{{ $role->value }}" :checked="$role === \App\Enums\RoleName::User" required />
                            {{ $role->label() }}
                        </label>
                    @endforeach
                </div>
                <x-atoms.form-error for="role" />
            </fieldset>

            <x-molecules.form-group name="password" type="password" :label="__('users.fields.password')" autocomplete="new-password" :required="! $id" />
            @if ($id)
                <p class="-mt-3.5 font-body text-body-sm text-on-surface-variant">{{ __('users.fields.password_hint') }}</p>
            @endif
            <x-molecules.form-group name="password_confirmation" type="password" :label="__('users.fields.password_confirmation')" autocomplete="new-password" :required="! $id" />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('users.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('users.index') }}" variant="secondary">
                    {{ __('users.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/users/form.js')
@endpush
