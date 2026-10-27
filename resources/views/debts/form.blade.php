@extends('layouts.dashboard')

@section('title', ($id ? __('debts.pages.'.$type.'.edit') : __('debts.pages.'.$type.'.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route($type === 'debt' ? 'debts.index' : 'receivables.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('debts.pages.'.$type.'.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('debts.pages.'.$type.'.edit') : __('debts.pages.'.$type.'.create') }}
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

            <input type="hidden" name="type" value="{{ $type }}">

            <x-molecules.form-group name="name" type="text" :label="__('debts.fields.name')" required autofocus />
            <div>
                <x-atoms.label for="account_id">{{ __('debts.fields.account') }}</x-atoms.label>
                <x-atoms.select name="account_id" required>
                    <option value="">{{ __('debts.fields.select_account') }}</option>
                </x-atoms.select>
                <x-atoms.form-error for="account_id" />
            </div>
            <x-molecules.form-group name="due_date" type="date" :label="__('debts.fields.due_date')" />
            <fieldset class="space-y-2">
                <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('sub-debts.mode.title') }}</legend>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                        <input type="radio" name="debt_mode" value="amount" checked>
                        {{ __('sub-debts.mode.amount') }}
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                        <input type="radio" name="debt_mode" value="sub_debts">
                        {{ __('sub-debts.mode.sub_debts') }}
                    </label>
                </div>
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('sub-debts.mode.hint') }}</p>
            </fieldset>
            <x-organisms.sub-debts-field />

            <x-molecules.form-group name="amount" type="text" inputmode="numeric" :label="__('debts.fields.amount')" data-money required />
            <x-molecules.form-group name="notes" type="text" :label="__('debts.fields.notes')" />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('debts.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route($type === 'debt' ? 'debts.index' : 'receivables.index') }}" variant="secondary">
                    {{ __('debts.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>

    @unless ($id)
        <x-organisms.modal id="debt-record-modal" :title="__('debts.record_prompt.title')">
            <p class="mb-6 font-body text-body-md text-on-surface-variant">
                {{ __('debts.record_prompt.body') }}
            </p>
            <div class="flex justify-end gap-3">
                <x-atoms.button type="button" variant="secondary" data-modal-close>
                    {{ __('debts.actions.cancel') }}
                </x-atoms.button>
                <x-atoms.button type="button" variant="secondary" data-record-choice="0">
                    {{ __('debts.record_prompt.no') }}
                </x-atoms.button>
                <x-atoms.button type="button" data-record-choice="1">
                    {{ __('debts.record_prompt.yes') }}
                </x-atoms.button>
            </div>
        </x-organisms.modal>
    @endunless
@endsection

@push('scripts')
    @vite('resources/js/debts/form.js')
@endpush
