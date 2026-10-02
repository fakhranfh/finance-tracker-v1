@extends('layouts.dashboard')

@section('title', ($id ? __('accounts.edit') : __('accounts.create')) . ' - ' . config('app.name', 'Laravel'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('accounts.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('accounts.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('accounts.edit') : __('accounts.create') }}
            </h1>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('accounts.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($id)
            <x-organisms.account-form-skeleton id="account-form-skeleton" />
        @endif

        <form
            id="account-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('accounts.flash.updated') : __('accounts.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('accounts.fields.name')" required autofocus />
            <x-molecules.form-group name="initial_balance" type="number" :label="__('accounts.fields.initial_balance')" step="1" min="0" required />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('accounts.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('accounts.index') }}" variant="secondary">
                    {{ __('accounts.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/accounts/form.js')
@endpush
