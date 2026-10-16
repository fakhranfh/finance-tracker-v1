@extends('layouts.dashboard')

@section('title', __('webhooks.logs.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ $backUrl }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ $backLabel }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('webhooks.logs.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant">
                {{ __('webhooks.logs.subtitle') }}
            </p>
        </div>

        <x-atoms.button type="button" id="clear-webhook-logs" variant="danger" data-webhook-id="{{ $id }}">
            {{ __('webhooks.logs.clear') }}
        </x-atoms.button>
    </div>

    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <x-molecules.table-toolbar
        id="webhook-logs-table-toolbar"
        :search-placeholder="__('webhooks.logs.table.search')"
        :per-page-label="__('webhooks.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="webhook-logs-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('webhooks.logs.table.search')"
        :per-page-label="__('webhooks.table.per_page')"
    />

    <div id="webhook-logs-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="webhook-logs-table"
            :selectable="false"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'empty' => __('webhooks.logs.table.empty'),
                    'expand' => __('webhooks.table.show_more'),
                    'collapse' => __('webhooks.table.show_less'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

    <x-organisms.modal id="clear-webhook-logs-modal" :title="__('webhooks.logs.confirm_clear')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('webhooks.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-clear-webhook-logs" variant="danger">
                {{ __('webhooks.logs.clear') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/webhooks/logs.js')
@endpush
