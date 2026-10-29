@extends('layouts.dashboard')

@section('title', __('users.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-headline-md font-bold text-on-surface">
                {{ __('users.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('users.subtitle') }}
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
                    aria-label="{{ __('users.actions.generate_count') }}"
                    title="{{ __('users.actions.generate_count') }}"
                />
                <x-atoms.button
                    type="button"
                    id="generate-users"
                    variant="secondary"
                    data-success-message="{{ __('users.flash.generated') }}"
                >
                    {{ __('users.actions.generate') }}
                </x-atoms.button>
            @endif
            <x-atoms.button href="{{ route('users.create') }}" class="col-span-2 sm:col-auto">
                {{ __('users.create') }}
            </x-atoms.button>
        </div>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <x-molecules.table-toolbar
        id="users-table-toolbar"
        :search-placeholder="__('users.table.search')"
        :per-page-label="__('users.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="users-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('users.table.search')"
        :per-page-label="__('users.table.per_page')"
    />

    <div id="users-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="users-table"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'bulkDelete' => __('users.actions.delete_selected'),
                    'selected' => __('users.table.selected'),
                    'empty' => __('users.table.empty'),
                    'selectAllMatching' => __('users.table.select_all_matching', ['total' => ':total']),
                    'allSelected' => __('users.table.all_selected', ['total' => ':total']),
                    'clearSelection' => __('users.table.clear_selection'),
                    'expand' => __('users.table.show_more'),
                    'collapse' => __('users.table.show_less'),
                    'edit' => __('users.actions.edit'),
                    'delete' => __('users.actions.delete'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="delete-user-modal" :title="__('users.actions.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('users.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-user" variant="danger">
                {{ __('users.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-users-modal" :title="__('users.actions.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('users.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-users" variant="danger">
                {{ __('users.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/users/index.js')
@endpush
