@extends('layouts.dashboard')

@section('title', __('debts.pages.'.$type.'.title'))

@section('content')
    @include('debts._tabs')

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('debts.pages.'.$type.'.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('debts.pages.'.$type.'.subtitle') }}
            </p>
        </div>

        <div class="grid w-full grid-cols-[6rem_1fr] items-center gap-3 sm:flex sm:w-auto">
            @if (app()->isLocal())
                <x-atoms.input
                    type="number"
                    name="generate-count"
                    id="generate-count"
                    value="10"
                    min="1"
                    max="50"
                    class="!w-24"
                    aria-label="{{ __('debts.actions.generate_count') }}"
                    title="{{ __('debts.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-debts"
                    variant="secondary"
                    data-success-message="{{ __('debts.flash.generated') }}"
                >
                    {{ __('debts.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button href="{{ route($type === 'debt' ? 'debts.create' : 'receivables.create') }}" class="col-span-2 sm:col-auto">
                {{ __('debts.pages.'.$type.'.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <div id="debts-filters" data-debt-type="{{ $type }}" data-payments-label="{{ __('debts.actions.payments') }}">
        <input type="hidden" name="type" value="{{ $type }}" data-table-filter>
    </div>

    <x-molecules.table-toolbar
        id="debts-table-toolbar"
        :search-placeholder="__('debts.table.search')"
        :per-page-label="__('debts.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="debts-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('debts.table.search')"
        :per-page-label="__('debts.table.per_page')"
    />

    <div id="debts-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="debts-table"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'bulkDelete' => __('debts.actions.delete_selected'),
                    'selected' => __('debts.table.selected'),
                    'empty' => __('debts.table.empty'),
                    'selectAllMatching' => __('debts.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('debts.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('debts.table.clear_selection'),
                    'expand' => __('debts.table.show_more'),
                    'collapse' => __('debts.table.show_less'),
                    'view' => __('debts.pages.'.$type.'.sub_debts'),
                    'edit' => __('debts.actions.edit'),
                    'delete' => __('debts.actions.delete'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-debt-modal" :title="__('debts.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('debts.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-debt" variant="danger">
                {{ __('debts.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-debts-modal" :title="__('debts.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('debts.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-debts" variant="danger">
                {{ __('debts.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/debts/index.js')
@endpush
