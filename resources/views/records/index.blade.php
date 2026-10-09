@extends('layouts.dashboard')

@section('title', __('records.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('records.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('records.subtitle') }}
            </p>        </div>

        <div class="grid w-full grid-cols-[6rem_1fr] items-center gap-3 sm:flex sm:w-auto">
            @if (app()->isLocal())
                <div id="generate-accounts-wrapper" class="relative col-span-2 hidden sm:col-auto">
                    <x-molecules.multi-select
                        id="generate-accounts"
                        name="account_ids"
                        :options="[]"
                        :all-label="__('records.actions.generate_accounts_summary', ['count' => 0])"
                        :selected-template="__('records.actions.generate_accounts_summary', ['count' => ':count'])"
                        :search-placeholder="__('records.filters.search_accounts')"
                        :select-all-label="__('records.filters.select_all')"
                        :deselect-all-label="__('records.filters.deselect_all')"
                        :checked="true"
                        summary-class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-lg border border-outline-variant px-3 py-2 font-body text-sm text-on-surface"
                    />
                </div>
                <x-atoms.input
                    type="number"
                    name="generate-count"
                    id="generate-count"
                    value="10"
                    min="1"
                    max="50"
                    class="!w-24"
                    aria-label="{{ __('records.actions.generate_count') }}"
                    title="{{ __('records.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-records"
                    variant="secondary"
                    data-success-message="{{ __('records.flash.generated') }}"
                >
                    {{ __('records.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button type="button" variant="secondary" data-export-format="xlsx">
                {{ __('records.actions.export_excel') }}
            </x-atoms.button>
            <x-atoms.button type="button" variant="secondary" data-export-format="pdf">
                {{ __('records.actions.export_pdf') }}
            </x-atoms.button>
            <x-atoms.button href="{{ route('records.create') }}" id="add-record" class="col-span-2 sm:col-auto" hidden>
                {{ __('records.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div id="records-no-accounts" class="mb-6 hidden flex-wrap items-center justify-between gap-3 rounded-lg border border-warning/20 bg-warning/10 px-4 py-3 font-body text-sm text-on-surface">
        <span>{{ __('records.no_accounts') }}</span>
        <x-atoms.button href="{{ route('accounts.create') }}">
            {{ __('accounts.create') }}
        </x-atoms.button>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <div id="records-summary" class="mb-6" data-records-label="{{ __('records.summary.records', ['count' => ':count']) }}">
        <x-organisms.records-summary-skeleton id="records-summary-skeleton" />

        <div id="records-summary-results" class="hidden">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.summary.income') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-success" data-summary-value="income"></p>
            </div>

            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.summary.expense') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-error" data-summary-value="expense"></p>
            </div>

            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.summary.transfer') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-on-surface">
                    {{ __('records.summary.transfer_in') }}: <span data-summary-value="transfer_in"></span>
                </p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-on-surface">
                    {{ __('records.summary.transfer_out') }}: <span data-summary-value="transfer_out"></span>
                </p>
            </div>

            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('records.summary.net') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-on-surface" data-summary-value="net"></p>
                <p class="mt-1 font-body text-body-sm text-on-surface-variant" data-summary-value="record_count"></p>
            </div>
        </div>
        </div>
    </div>

    <x-molecules.table-toolbar
        id="records-table-toolbar"
        :search-placeholder="__('records.table.search')"
        :per-page-label="__('records.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="records-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('records.table.search')"
        :per-page-label="__('records.table.per_page')"
    />

    <div id="records-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="records-table"
            data-type-labels="{{ json_encode($typeLabels) }}"
            data-uncategorized="{{ __('records.fields.no_category') }}"
            :headers="$tableHeaders"
            :filters="$filterConfig"
            filters-id="records-filters"
            :config="[
                'labels' => [
                    'bulkDelete' => __('records.actions.delete_selected'),
                    'selected' => __('records.table.selected'),
                    'total' => __('records.table.total', ['total' => ':total']),
                    'clearFilters' => __('records.filters.clear'),
                    'empty' => __('records.table.empty'),
                    'selectAllMatching' => __('records.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('records.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('records.table.clear_selection'),
                    'expand' => __('records.table.show_more'),
                    'collapse' => __('records.table.show_less'),
                    'edit' => __('records.actions.edit'),
                    'delete' => __('records.actions.delete'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-record-modal" :title="__('records.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('records.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-record" variant="danger">
                {{ __('records.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-records-modal" :title="__('records.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('records.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-records" variant="danger">
                {{ __('records.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/records/index.js')
@endpush
