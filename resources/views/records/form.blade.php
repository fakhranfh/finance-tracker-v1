@extends('layouts.dashboard')

@section('title', ($id ? __('records.edit') : __('records.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('records.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('records.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('records.edit') : __('records.create') }}
            </h1>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('records.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($accounts->isEmpty())
            <p class="mb-4 font-body text-body-md text-on-surface-variant">
                {{ __('records.no_accounts') }}
            </p>
            <x-atoms.button href="{{ route('accounts.create') }}">
                {{ __('accounts.create') }}
            </x-atoms.button>
        @else
        @if ($id)
            <x-organisms.record-form-skeleton id="record-form-skeleton" />
        @endif

        <form
            id="record-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('records.flash.updated') : __('records.flash.created') }}"
        >
            @csrf

            <div class="space-y-1.5">
                <x-atoms.label>{{ __('records.fields.type') }}</x-atoms.label>
                <div class="grid grid-cols-3 gap-2" role="radiogroup">
                    @foreach ($typeLabels as $value => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-md border border-outline-variant px-3 py-2.5 font-body text-body-md text-on-surface-variant transition-colors has-checked:border-primary has-checked:bg-primary-fixed has-checked:font-medium has-checked:text-primary has-focus-visible:ring-2 has-focus-visible:ring-primary/20">
                            <input type="radio" name="type" value="{{ $value }}" class="sr-only" @checked($value === 'expense') required>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <x-atoms.form-error for="type" />
            </div>

            <div class="space-y-1.5">
                <x-atoms.label for="account_id">
                    <span data-account-label data-default="{{ __('records.fields.account') }}" data-transfer="{{ __('records.fields.from_account') }}">{{ __('records.fields.account') }}</span>
                </x-atoms.label>
                <x-atoms.select name="account_id" required>
                    <option value="">{{ __('records.fields.select_account') }}</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </x-atoms.select>
                <x-atoms.form-error for="account_id" />
            </div>

            <div class="hidden space-y-1.5" data-transfer-only>
                <x-atoms.label for="to_account_id">{{ __('records.fields.to_account') }}</x-atoms.label>
                <x-atoms.select name="to_account_id">
                    <option value="">{{ __('records.fields.select_account') }}</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </x-atoms.select>
                <x-atoms.form-error for="to_account_id" />
            </div>

            <x-molecules.form-group name="amount" type="number" :label="__('records.fields.amount')" step="1" min="1" required />
            <x-molecules.form-group name="recorded_at" type="datetime-local" :label="__('records.fields.recorded_at')" required />
            <x-molecules.form-group name="note" type="text" :label="__('records.fields.note')" />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('records.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('records.index') }}" variant="secondary">
                    {{ __('records.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
        @endif
    </div>
@endsection

@push('scripts')
    @vite('resources/js/records/form.js')
@endpush
