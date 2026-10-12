@extends('layouts.dashboard')

@section('title', __('goals.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('goals.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('goals.subtitle') }}
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
                    aria-label="{{ __('goals.actions.generate_count') }}"
                    title="{{ __('goals.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-goals"
                    variant="secondary"
                    data-success-message="{{ __('goals.flash.generated') }}"
                >
                    {{ __('goals.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button href="{{ route('goals.create') }}" class="col-span-2 sm:col-auto">
                {{ __('goals.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>
    <div data-status-error role="alert" class="mb-6 hidden rounded-lg border border-error/20 bg-error/10 px-4 py-3 font-body text-sm text-error">{{ __('goals.flash.status_failed') }}</div>

    <x-molecules.table-toolbar
        id="goals-table-toolbar"
        :search-placeholder="__('goals.table.search')"
        :per-page-label="__('goals.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="goals-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('goals.table.search')"
        :per-page-label="__('goals.table.per_page')"
    />

    <div id="goals-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="goals-table"
            data-icons="{{ json_encode($icons) }}"
            data-status-labels="{{ json_encode($statusLabels) }}"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'bulkDelete' => __('goals.actions.delete_selected'),
                    'selected' => __('goals.table.selected'),
                    'empty' => __('goals.table.empty'),
                    'selectAllMatching' => __('goals.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('goals.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('goals.table.clear_selection'),
                    'expand' => __('goals.table.show_more'),
                    'collapse' => __('goals.table.show_less'),
                    'view' => __('goals.actions.view'),
                    'edit' => __('goals.actions.edit'),
                    'delete' => __('goals.actions.delete'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-goal-modal" :title="__('goals.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('goals.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-goal" variant="danger">
                {{ __('goals.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-goals-modal" :title="__('goals.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('goals.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-goals" variant="danger">
                {{ __('goals.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/goals/index.js')
@endpush
