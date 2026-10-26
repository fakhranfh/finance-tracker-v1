@extends('layouts.dashboard')

@section('title', __('debt-payments.pages.'.$type.'.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route($type === 'debt' ? 'debts.index' : 'receivables.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('debt-payments.pages.'.$type.'.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('debt-payments.pages.'.$type.'.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant" data-debt-name>
                {{ __('debt-payments.pages.'.$type.'.subtitle') }}
            </p>
        </div>

        <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto">
            <x-atoms.button href="{{ route($type === 'debt' ? 'debts.payments.existing' : 'receivables.payments.existing', $debtId) }}" id="existing-payment-link" variant="secondary">
                {{ __('debt-payments.pages.'.$type.'.existing') }}
            </x-atoms.button>
            <x-atoms.button href="{{ route($type === 'debt' ? 'debts.payments.create' : 'receivables.payments.create', $debtId) }}" id="add-payment-link">
                {{ __('debt-payments.pages.'.$type.'.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div
        id="debt-payments-page"
        data-debt-id="{{ $debtId }}"
        data-type="{{ $type }}"
        data-summary-template="{{ __('debt-payments.summary', ['paid' => ':paid', 'amount' => ':amount', 'remaining' => ':remaining']) }}"
        data-removed-message="{{ __('debt-payments.flash.removed') }}"
    ></div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <p class="mb-4 hidden font-body text-body-md font-medium text-on-surface" data-payments-summary></p>

    <x-molecules.table-toolbar
        id="debt-payments-table-toolbar"
        :search-placeholder="__('debt-payments.table.search')"
        :per-page-label="__('debt-payments.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="debt-payments-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('debt-payments.table.search')"
        :per-page-label="__('debt-payments.table.per_page')"
    />

    <div id="debt-payments-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="debt-payments-table"
            :selectable="false"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'empty' => __('debt-payments.table.empty'),
                    'expand' => __('debt-payments.table.show_more'),
                    'collapse' => __('debt-payments.table.show_less'),
                    'view' => __('debt-payments.actions.open_record'),
                    'delete' => __('debt-payments.actions.remove'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-payment-modal" :title="__('debt-payments.actions.confirm_remove')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('debt-payments.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-payment" variant="danger">
                {{ __('debt-payments.actions.remove') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/debt-payments/index.js')
@endpush
