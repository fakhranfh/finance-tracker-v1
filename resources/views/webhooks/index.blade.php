@extends('layouts.dashboard')

@section('title', __('webhooks.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('webhooks.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('webhooks.subtitle') }}
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
                    aria-label="{{ __('webhooks.actions.generate_count') }}"
                    title="{{ __('webhooks.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-webhooks"
                    variant="secondary"
                    data-success-message="{{ __('webhooks.flash.generated') }}"
                >
                    {{ __('webhooks.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button href="{{ route('webhooks.create') }}" class="col-span-2 sm:col-auto">
                {{ __('webhooks.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <x-organisms.webhook-guide />

    <x-molecules.table-toolbar
        id="webhooks-table-toolbar"
        :search-placeholder="__('webhooks.table.search')"
        :per-page-label="__('webhooks.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="webhooks-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('webhooks.table.search')"
        :per-page-label="__('webhooks.table.per_page')"
    />

    <div id="webhooks-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="webhooks-table"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'bulkDelete' => __('webhooks.actions.delete_selected'),
                    'selected' => __('webhooks.table.selected'),
                    'empty' => __('webhooks.table.empty'),
                    'selectAllMatching' => __('webhooks.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('webhooks.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('webhooks.table.clear_selection'),
                    'expand' => __('webhooks.table.show_more'),
                    'collapse' => __('webhooks.table.show_less'),
                    'edit' => __('webhooks.actions.edit'),
                    'delete' => __('webhooks.actions.delete'),
                    'view' => __('webhooks.actions.test'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-webhook-modal" :title="__('webhooks.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('webhooks.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-webhook" variant="danger">
                {{ __('webhooks.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-webhooks-modal" :title="__('webhooks.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('webhooks.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-webhooks" variant="danger">
                {{ __('webhooks.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/webhooks/index.js')
@endpush
