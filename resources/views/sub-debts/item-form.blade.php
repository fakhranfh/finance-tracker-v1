@extends('layouts.dashboard')

@section('title', ($id ? __('sub-debts.pages.'.$type.'.edit_item') : __('sub-debts.pages.'.$type.'.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route($type === 'debt' ? 'debts.sub-debts.index' : 'receivables.sub-debts.index', $debtId) }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('sub-debts.pages.'.$type.'.back_to_list') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('sub-debts.pages.'.$type.'.edit_item') : __('sub-debts.pages.'.$type.'.create') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant" data-debt-name></p>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('sub-debts.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($id)
            <x-organisms.sub-debt-item-form-skeleton id="sub-debt-item-form-skeleton" />
        @endif

        <form
            id="sub-debt-item-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            data-debt-id="{{ $debtId }}"
            data-type="{{ $type }}"
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('sub-debts.flash.updated_item') : __('sub-debts.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('sub-debts.fields.name')" maxlength="255" required autofocus />
            <x-molecules.form-group name="amount" type="text" inputmode="numeric" :label="__('sub-debts.fields.amount')" data-money required />
            <x-molecules.form-group name="paid_amount" type="text" inputmode="numeric" :label="__('sub-debts.fields.paid_amount')" value="0" data-money />
            <x-molecules.form-group name="due_date" type="date" :label="__('sub-debts.fields.due_date')" />
            <x-molecules.form-group name="note" type="text" :label="__('sub-debts.fields.note')" maxlength="1000" />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('sub-debts.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route($type === 'debt' ? 'debts.sub-debts.index' : 'receivables.sub-debts.index', $debtId) }}" variant="secondary">
                    {{ __('sub-debts.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/sub-debts/item-form.js')
@endpush
