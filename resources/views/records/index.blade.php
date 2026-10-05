@extends('layouts.dashboard')

@section('title', __('records.title') . ' - ' . config('app.name', 'Laravel'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('records.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('records.subtitle') }}
            </p>
        </div>

        <div class="grid w-full grid-cols-[6rem_1fr] items-center gap-3 sm:flex sm:w-auto">
            @if (app()->isLocal())
                @if ($accounts->isNotEmpty())
                    <details class="relative col-span-2 sm:col-auto" id="generate-accounts">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-lg border border-outline-variant px-3 py-2 font-body text-sm text-on-surface">
                            <span data-accounts-summary data-summary-template="{{ __('records.actions.generate_accounts_summary', ['count' => ':count']) }}">{{ __('records.actions.generate_accounts_summary', ['count' => $accounts->count()]) }}</span>
                            <span aria-hidden="true">▾</span>
                        </summary>
                        <div class="absolute z-20 mt-2 max-h-64 w-64 overflow-y-auto rounded-lg border border-outline-variant bg-surface p-2 shadow-lg">
                            <p class="px-2 pb-2 font-body text-xs text-on-surface-variant">{{ __('records.actions.generate_accounts') }}</p>
                            @foreach ($accounts as $account)
                                <label class="flex cursor-pointer items-center gap-3 rounded px-2 py-2 font-body text-sm text-on-surface hover:bg-surface-container">
                                    <x-atoms.checkbox name="account_ids[]" id="generate-account-{{ $account->id }}" value="{{ $account->id }}" checked data-generate-account />
                                    <span>{{ $account->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </details>
                @endif
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
            @if ($hasAccounts)
                <x-atoms.button href="{{ route('records.create') }}" class="col-span-2 sm:col-auto">
                    {{ __('records.create') }}
                </x-atoms.button>
            @endif
        </div>
    </div>

    @unless ($hasAccounts)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-warning/20 bg-warning/10 px-4 py-3 font-body text-sm text-on-surface">
            <span>{{ __('records.no_accounts') }}</span>
            <x-atoms.button href="{{ route('accounts.create') }}">
                {{ __('accounts.create') }}
            </x-atoms.button>
        </div>
    @endunless

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

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

    <x-organisms.records-table-skeleton id="records-table-skeleton" :headers="$tableHeaders" />

    <div id="records-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="records-table"
            data-type-labels="{{ json_encode($typeLabels) }}"
            :headers="$tableHeaders"
            :filters="$filterConfig"
            filters-id="records-filters"
            :config="[
                'labels' => [
                    'bulkDelete' => __('records.actions.delete_selected'),
                    'selected' => __('records.table.selected'),
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
