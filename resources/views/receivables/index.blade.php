@extends('layouts.dashboard')

@section('title', __('receivables.title'))

@section('content')
    @include('debts._tabs')

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('receivables.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('receivables.subtitle') }}
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
                    aria-label="{{ __('receivables.actions.generate_count') }}"
                    title="{{ __('receivables.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-receivables"
                    variant="secondary"
                    data-success-message="{{ __('receivables.flash.generated') }}"
                >
                    {{ __('receivables.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button href="{{ route('receivables.create') }}" class="col-span-2 sm:col-auto">
                {{ __('receivables.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <x-molecules.table-toolbar
        id="receivables-table-toolbar"
        :search-placeholder="__('receivables.table.search')"
        :per-page-label="__('receivables.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="receivables-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('receivables.table.search')"
        :per-page-label="__('receivables.table.per_page')"
    />

    <div id="receivables-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="receivables-table"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'bulkDelete' => __('receivables.actions.delete_selected'),
                    'selected' => __('receivables.table.selected'),
                    'empty' => __('receivables.table.empty'),
                    'selectAllMatching' => __('receivables.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('receivables.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('receivables.table.clear_selection'),
                    'expand' => __('receivables.table.show_more'),
                    'collapse' => __('receivables.table.show_less'),
                    'edit' => __('receivables.actions.edit'),
                    'delete' => __('receivables.actions.delete'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-receivable-modal" :title="__('receivables.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('receivables.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-receivable" variant="danger">
                {{ __('receivables.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-receivables-modal" :title="__('receivables.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('receivables.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-receivables" variant="danger">
                {{ __('receivables.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/receivables/index.js')
@endpush
