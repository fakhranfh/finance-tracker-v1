@extends('layouts.dashboard')

@section('title', ($id ? __('receivables.edit') : __('receivables.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('receivables.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('receivables.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('receivables.edit') : __('receivables.create') }}
            </h1>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('receivables.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($id)
            <x-organisms.receivable-form-skeleton id="receivable-form-skeleton" />
        @endif

        <form
            id="receivable-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('receivables.flash.updated') : __('receivables.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('receivables.fields.name')" required autofocus />
            <x-molecules.form-group name="amount" type="number" :label="__('receivables.fields.amount')" step="0.01" min="0" required />
            <x-molecules.form-group name="paid_amount" type="number" :label="__('receivables.fields.paid_amount')" step="0.01" min="0" required />
            <x-molecules.form-group name="due_date" type="date" :label="__('receivables.fields.due_date')" />
            <x-molecules.form-group name="notes" type="text" :label="__('receivables.fields.notes')" />
            <div class="flex items-center gap-2">
                <x-atoms.checkbox name="is_paid" id="is_paid" />
                <x-atoms.label for="is_paid">{{ __('receivables.fields.is_paid') }}</x-atoms.label>
            </div>

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('receivables.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('receivables.index') }}" variant="secondary">
                    {{ __('receivables.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/receivables/form.js')
@endpush
