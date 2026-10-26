@extends('layouts.dashboard')

@section('title', ($id ? __('debts.edit') : __('debts.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('debts.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('debts.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('debts.edit') : __('debts.create') }}
            </h1>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('debts.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($id)
            <x-organisms.debt-form-skeleton id="debt-form-skeleton" />
        @endif

        <form
            id="debt-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('debts.flash.updated') : __('debts.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('debts.fields.name')" required autofocus />
            <x-molecules.form-group name="amount" type="number" :label="__('debts.fields.amount')" step="0.01" min="0" required />
            <x-molecules.form-group name="paid_amount" type="number" :label="__('debts.fields.paid_amount')" step="0.01" min="0" required />
            <x-molecules.form-group name="due_date" type="date" :label="__('debts.fields.due_date')" />
            <x-molecules.form-group name="notes" type="text" :label="__('debts.fields.notes')" />
            <div class="flex items-center gap-2">
                <x-atoms.checkbox name="is_paid" id="is_paid" />
                <x-atoms.label for="is_paid">{{ __('debts.fields.is_paid') }}</x-atoms.label>
            </div>

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('debts.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('debts.index') }}" variant="secondary">
                    {{ __('debts.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/debts/form.js')
@endpush
