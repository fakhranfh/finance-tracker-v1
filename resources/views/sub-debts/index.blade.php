@extends('layouts.dashboard')

@section('title', __('sub-debts.pages.'.$type.'.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route($type === 'debt' ? 'debts.index' : 'receivables.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('sub-debts.pages.'.$type.'.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('sub-debts.pages.'.$type.'.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant" data-debt-name>
                {{ __('sub-debts.pages.'.$type.'.subtitle') }}
            </p>
        </div>

        <div class="grid w-full grid-cols-[6rem_1fr] items-center gap-3 sm:flex sm:w-auto">
            @if (app()->isLocal())
                <x-atoms.input
                    type="number"
                    name="generate-count"
                    id="generate-count"
                    value="5"
                    min="1"
                    max="50"
                    class="!w-24"
                    aria-label="{{ __('sub-debts.actions.generate_count') }}"
                    title="{{ __('sub-debts.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-sub-debts"
                    variant="secondary"
                    data-success-message="{{ __('sub-debts.flash.generated') }}"
                >
                    {{ __('sub-debts.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button href="{{ route($type === 'debt' ? 'debts.sub-debts.create' : 'receivables.sub-debts.create', $debtId) }}" class="col-span-2 sm:col-auto">
                {{ __('sub-debts.pages.'.$type.'.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div
        id="sub-debts-page"
        data-debt-id="{{ $debtId }}"
        data-paid-label="{{ __('sub-debts.table.paid') }}"
        data-unpaid-label="{{ __('sub-debts.table.unpaid') }}"
        data-summary-template="{{ __('sub-debts.summary', ['paid' => ':paid', 'total' => ':total', 'amount' => ':amount', 'paid_amount' => ':paid_amount']) }}"
    ></div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>
    <div data-status-error role="alert" class="mb-6 hidden rounded-lg border border-error/20 bg-error/10 px-4 py-3 font-body text-sm text-error">{{ __('sub-debts.flash.status_failed') }}</div>

    <p class="mb-4 hidden font-body text-body-md font-medium text-on-surface" data-sub-debts-summary></p>

    <x-molecules.table-toolbar
        id="sub-debts-table-toolbar"
        :search-placeholder="__('sub-debts.table.search')"
        :per-page-label="__('sub-debts.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="sub-debts-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('sub-debts.table.search')"
        :per-page-label="__('sub-debts.table.per_page')"
    />

    <div id="sub-debts-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="sub-debts-table"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'bulkDelete' => __('sub-debts.actions.delete_selected'),
                    'selected' => __('sub-debts.table.selected'),
                    'selectAllMatching' => __('sub-debts.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('sub-debts.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('sub-debts.table.clear_selection'),
                    'empty' => __('sub-debts.table.empty'),
                    'expand' => __('sub-debts.table.show_more'),
                    'collapse' => __('sub-debts.table.show_less'),
                    'edit' => __('sub-debts.actions.edit'),
                    'delete' => __('sub-debts.actions.delete'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-sub-debt-modal" :title="__('sub-debts.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('sub-debts.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-sub-debt" variant="danger">
                {{ __('sub-debts.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-sub-debts-modal" :title="__('sub-debts.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('sub-debts.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-sub-debts" variant="danger">
                {{ __('sub-debts.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/sub-debts/index.js')
@endpush
