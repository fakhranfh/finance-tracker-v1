@extends('layouts.dashboard')

@section('title', __('sub-debts.pages.'.$type.'.edit'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route($type === 'debt' ? 'debts.sub-debts.index' : 'receivables.sub-debts.index', $debtId) }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('sub-debts.pages.'.$type.'.back_to_list') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('sub-debts.pages.'.$type.'.edit') }}
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
        <x-organisms.sub-debt-form-skeleton id="sub-debt-form-skeleton" />

        <form
            id="sub-debt-form"
            class="hidden space-y-5"
            novalidate
            data-debt-id="{{ $debtId }}"
            data-type="{{ $type }}"
            data-success-message="{{ __('sub-debts.flash.updated') }}"
        >
            @csrf

            <input type="hidden" name="amount">
            <input type="hidden" name="due_date">

            <x-organisms.sub-debts-field />

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
    @vite('resources/js/sub-debts/form.js')
@endpush
