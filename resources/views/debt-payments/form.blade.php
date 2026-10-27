@extends('layouts.dashboard')

@section('title', __('debt-payments.pages.'.$type.'.create'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route($type === 'debt' ? 'debts.payments.index' : 'receivables.payments.index', $debtId) }}" id="back-to-payments" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('debt-payments.pages.'.$type.'.back_to_payments') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('debt-payments.pages.'.$type.'.create') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant" data-debt-name></p>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('debt-payments.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        <form
            id="debt-payment-form"
            class="space-y-5"
            novalidate
            data-debt-id="{{ $debtId }}"
            data-type="{{ $type }}"
            data-success-message="{{ __('debt-payments.flash.created') }}"
        >
            @csrf

            <div class="hidden space-y-1.5" data-sub-debt-group>
                <x-atoms.label for="sub_debt_id">{{ __('debt-payments.fields.sub_debt') }}</x-atoms.label>
                <x-atoms.select name="sub_debt_id" id="sub_debt_id">
                    <option value="">{{ __('debt-payments.fields.select_sub_debt') }}</option>
                </x-atoms.select>
                <x-atoms.form-error for="sub_debt_id" />
            </div>

            <div class="space-y-1.5">
                <x-atoms.label for="account_id">{{ __('debt-payments.fields.account') }}</x-atoms.label>
                <x-atoms.select name="account_id" id="account_id" required>
                    <option value="">{{ __('debt-payments.fields.select_account') }}</option>
                </x-atoms.select>
                <x-atoms.form-error for="account_id" />
            </div>

            <div class="space-y-1.5">
                <x-atoms.label for="category_id">{{ __('debt-payments.fields.category') }}</x-atoms.label>
                <x-atoms.select name="category_id" id="category_id">
                    <option value="">{{ __('debt-payments.fields.no_category') }}</option>
                </x-atoms.select>
                <x-atoms.form-error for="category_id" />
            </div>

            <x-molecules.form-group name="amount" type="text" inputmode="numeric" :label="__('debt-payments.fields.amount')" data-money required />
            <x-molecules.form-group name="recorded_at" type="datetime-local" :label="__('debt-payments.fields.recorded_at')" required />
            <x-molecules.form-group name="note" type="text" :label="__('debt-payments.fields.note')" />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('debt-payments.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route($type === 'debt' ? 'debts.payments.index' : 'receivables.payments.index', $debtId) }}" variant="secondary">
                    {{ __('debt-payments.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>

    <x-organisms.modal id="debt-payment-record-modal" :title="__('debt-payments.record_prompt.title')">
        <p class="mb-6 font-body text-body-md text-on-surface-variant">
            {{ __('debt-payments.record_prompt.body') }}
        </p>
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('debt-payments.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" variant="secondary" data-record-choice="0">
                {{ __('debt-payments.record_prompt.no') }}
            </x-atoms.button>
            <x-atoms.button type="button" data-record-choice="1">
                {{ __('debt-payments.record_prompt.yes') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/debt-payments/form.js')
@endpush
