@extends('layouts.dashboard')

@section('title', __('categories.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('categories.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('categories.subtitle') }}
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
                    aria-label="{{ __('categories.actions.generate_count') }}"
                    title="{{ __('categories.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-categories"
                    variant="secondary"
                    data-success-message="{{ __('categories.flash.generated') }}"
                >
                    {{ __('categories.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button href="{{ route('categories.create') }}" class="col-span-2 sm:col-auto">
                {{ __('categories.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <x-molecules.table-toolbar
        id="categories-table-toolbar"
        :search-placeholder="__('categories.table.search')"
        :per-page-label="__('categories.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="categories-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('categories.table.search')"
        :per-page-label="__('categories.table.per_page')"
    />

    <div id="categories-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="categories-table"
            data-type-labels="{{ json_encode($typeLabels) }}"
            data-icons="{{ json_encode($icons) }}"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'bulkDelete' => __('categories.actions.delete_selected'),
                    'selected' => __('categories.table.selected'),
                    'empty' => __('categories.table.empty'),
                    'selectAllMatching' => __('categories.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('categories.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('categories.table.clear_selection'),
                    'expand' => __('categories.table.show_more'),
                    'collapse' => __('categories.table.show_less'),
                    'edit' => __('categories.actions.edit'),
                    'delete' => __('categories.actions.delete'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-category-modal" :title="__('categories.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('categories.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-category" variant="danger">
                {{ __('categories.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-categories-modal" :title="__('categories.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('categories.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-categories" variant="danger">
                {{ __('categories.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/categories/index.js')
@endpush
