@extends('layouts.dashboard')

@section('title', __('accounts.title'))

@php
    $tableHeaders = [
        ['label' => __('accounts.table.name'), 'sort' => 'name'],
        ['label' => __('accounts.table.balance'), 'sort' => 'balance'],
        ['label' => __('accounts.table.created_at'), 'sort' => 'created_at'],
        __('accounts.table.actions'),
    ];
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('accounts.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('accounts.subtitle') }}
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
                    aria-label="{{ __('accounts.actions.generate_count') }}"
                    title="{{ __('accounts.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-accounts"
                    variant="secondary"
                    data-success-message="{{ __('accounts.flash.generated') }}"
                >
                    {{ __('accounts.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button type="button" id="open-reorder-accounts" variant="secondary">
                {{ __('accounts.order.start') }}
            </x-atoms.button>
            <x-atoms.button href="{{ route('accounts.create') }}" class="col-span-2 sm:col-auto">
                {{ __('accounts.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <x-molecules.table-toolbar
        id="accounts-table-toolbar"
        :search-placeholder="__('accounts.table.search')"
        :per-page-label="__('accounts.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="accounts-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('accounts.table.search')"
        :per-page-label="__('accounts.table.per_page')"
    />

    <div id="accounts-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="accounts-table"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'bulkDelete' => __('accounts.actions.delete_selected'),
                    'selected' => __('accounts.table.selected'),
                    'empty' => __('accounts.table.empty'),
                    'selectAllMatching' => __('accounts.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('accounts.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('accounts.table.clear_selection'),
                    'expand' => __('accounts.table.show_more'),
                    'collapse' => __('accounts.table.show_less'),
                    'edit' => __('accounts.actions.edit'),
                    'delete' => __('accounts.actions.delete'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="reorder-accounts-modal" :title="__('accounts.order.title')">
        <ol
            id="reorder-accounts-list"
            class="max-h-[60vh] space-y-2 overflow-y-auto pr-1"
            data-label-move-up="{{ __('accounts.order.move_up') }}"
            data-label-move-down="{{ __('accounts.order.move_down') }}"
        ></ol>

        <div class="mt-5 flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('accounts.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="save-reorder-accounts">
                {{ __('accounts.actions.save') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="delete-account-modal" :title="__('accounts.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('accounts.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-account" variant="danger">
                {{ __('accounts.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-accounts-modal" :title="__('accounts.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('accounts.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-accounts" variant="danger">
                {{ __('accounts.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/accounts/index.js')
@endpush
