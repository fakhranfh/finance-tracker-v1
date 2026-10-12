@extends('layouts.dashboard')

@section('title', __('accounts.summary.title'))

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('accounts.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('accounts.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                <x-atoms.skeleton id="account-name-skeleton" class="h-8 w-48" />
                <span id="account-name" class="hidden" data-not-found="{{ __('accounts.summary.not_found') }}"></span>
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">{{ __('accounts.summary.subtitle') }}</p>
        </div>

        <x-atoms.button href="{{ route('accounts.edit', $id) }}" variant="secondary" class="shrink-0">
            {{ __('accounts.actions.edit') }}
        </x-atoms.button>
    </div>

    <div class="max-sm:pb-20" id="account-summary" data-account-id="{{ $id }}" data-records-label="{{ __('accounts.summary.records', ['count' => ':count']) }}" data-type-labels="{{ json_encode($typeLabels) }}" data-uncategorized="{{ __('records.fields.no_category') }}">
        <div data-summary-filter-inline class="mb-6 hidden sm:block">
            <form id="account-summary-filter" method="GET" action="{{ route('accounts.summary', $id) }}" class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                @include('accounts.partials.summary-filter-fields', ['presets' => $presets, 'range' => $range])
            </form>
        </div>

        {{-- Shown in place of the summary cards and the record list while the filter loads. --}}
        <div id="account-summary-skeleton" class="hidden space-y-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-atoms.skeleton class="h-28 rounded-lg" />
                <x-atoms.skeleton class="h-28 rounded-lg" />
                <x-atoms.skeleton class="h-28 rounded-lg" />
                <x-atoms.skeleton class="h-28 rounded-lg" />
            </div>

            <div>
                {{-- Mirrors the record table: header row and body rows on desktop, stacked cards on mobile. --}}
                <div class="space-y-3 sm:hidden">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="space-y-3 rounded-lg border border-outline-variant bg-surface p-4">
                            <x-atoms.skeleton class="h-4 w-1/2" />
                            <x-atoms.skeleton class="h-4 w-full" />
                            <x-atoms.skeleton class="h-4 w-3/4" />
                        </div>
                    @endfor
                </div>

                <div class="overflow-hidden rounded-lg border border-outline-variant bg-surface max-sm:hidden">
                    <div class="grid grid-cols-5 gap-4 border-b border-outline-variant bg-surface-container px-4 py-3">
                        @for ($i = 0; $i < 5; $i++)
                            <x-atoms.skeleton class="h-4 w-16" />
                        @endfor
                    </div>
                    @for ($row = 0; $row < 5; $row++)
                        <div class="grid grid-cols-5 gap-4 border-b border-outline-variant px-4 py-3 last:border-b-0">
                            @for ($i = 0; $i < 5; $i++)
                                <x-atoms.skeleton class="h-4 w-full" />
                            @endfor
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <div id="account-summary-results">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('accounts.summary.income') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-success" data-summary-value="income">…</p>
            </div>

            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('accounts.summary.expense') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-error" data-summary-value="expense">…</p>
            </div>

            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('accounts.summary.transfer') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-on-surface">
                    {{ __('accounts.summary.transfer_in') }}: <span data-summary-value="transfer_in">…</span>
                </p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-on-surface">
                    {{ __('accounts.summary.transfer_out') }}: <span data-summary-value="transfer_out">…</span>
                </p>
            </div>

            <div class="rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('accounts.summary.net') }}</p>
                <p class="mt-2 font-heading text-headline-sm font-bold text-on-surface" data-summary-value="net">…</p>
                <p class="mt-1 font-body text-body-sm text-on-surface-variant" data-summary-value="record_count">…</p>
            </div>
        </div>

        <h2 class="mb-3 mt-8 font-heading text-title-md font-bold text-on-surface">{{ __('accounts.summary.records_title') }}</h2>

        @if (app()->isLocal())
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <x-atoms.input
                    type="number"
                    name="generate-count"
                    id="account-generate-count"
                    value="10"
                    min="1"
                    max="1000"
                    class="!w-24"
                    aria-label="{{ __('records.actions.generate_count') }}"
                    title="{{ __('records.actions.generate_count') }}"
                />
                <x-atoms.select
                    name="generate-type"
                    id="account-generate-type"
                    class="!w-40"
                    aria-label="{{ __('records.actions.generate_type') }}"
                    title="{{ __('records.actions.generate_type') }}"
                >
                    <option value="">{{ __('records.actions.generate_type_random') }}</option>
                    @foreach ($typeLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-atoms.select>
                <x-atoms.input
                    type="text"
                    inputmode="decimal"
                    data-money
                    name="generate-amount"
                    id="account-generate-amount"
                    placeholder="{{ __('records.actions.generate_amount_random') }}"
                    class="!w-40"
                    aria-label="{{ __('records.actions.generate_amount') }}"
                    title="{{ __('records.actions.generate_amount') }}"
                />
                <x-atoms.button
                    type="button"
                    id="account-generate-records"
                    variant="secondary"
                    data-success-message="{{ __('records.flash.generate_queued') }}"
                >
                    {{ __('records.actions.generate') }}
                </x-atoms.button>
                <x-atoms.button
                    type="button"
                    id="account-delete-records"
                    variant="secondary"
                    data-confirm="{{ __('records.actions.delete_all_confirm') }}"
                >
                    {{ __('records.actions.delete_all') }}
                </x-atoms.button>
            </div>
        @endif

        <div data-flash-success class="mb-4 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

        <x-molecules.table-toolbar
            id="account-records-toolbar"
            :search-placeholder="__('records.table.search')"
            :per-page-label="__('records.table.per_page')"
        />

        <div id="account-records-wrapper" class="hidden max-sm:pb-20">
            <x-organisms.table
                id="account-records-table"
                :selectable="false"
                :headers="$tableHeaders"
                :config="[
                    'labels' => [
                        'selected' => __('records.table.selected'),
                        'total' => __('records.table.total', ['total' => ':total']),
                        'empty' => __('records.table.empty'),
                        'selectAllMatching' => __('records.table.select_all_matching', ['total' => ':total']),
                        'allSelected' => __('records.table.all_selected', ['total' => ':total']),
                        'clearSelection' => __('records.table.clear_selection'),
                        'expand' => __('records.table.show_more'),
                        'collapse' => __('records.table.show_less'),
                        'edit' => __('records.actions.edit'),
                    ],
                ]"
            />

            <x-molecules.pagination data-pagination />
        </div>
        </div>
    </div>

    {{-- Mobile: same bottom bar as the table; the filter form moves into this modal on small screens. --}}
    <div class="fixed inset-x-0 bottom-0 z-40 flex gap-3 border-t border-outline-variant bg-surface p-3 sm:hidden">
        <x-atoms.button type="button" variant="secondary" class="flex-1" data-modal-open="account-summary-filter-modal">
            {{ __('common.table.filter') }}
        </x-atoms.button>
    </div>

    <x-organisms.modal id="account-summary-filter-modal" :title="__('common.table.filter')">
        <div data-summary-filter-slot></div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/accounts/summary.js')
@endpush
