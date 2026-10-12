@extends('layouts.dashboard')

@section('title', __('sub-goals.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('goals.show', $goalId) }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('sub-goals.back_to_goal') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('sub-goals.title') }}
            </h1>
            <p class="mt-1 font-body text-body-md text-on-surface-variant" data-goal-name>
                {{ __('sub-goals.subtitle') }}
            </p>
        </div>

        <x-atoms.button href="{{ route('goals.sub-goals.edit', $goalId) }}" id="edit-sub-goals-link" variant="secondary">
            {{ __('sub-goals.form.edit') }}
        </x-atoms.button>

    </div>

    <div
        id="sub-goals-page"
        data-goal-id="{{ $goalId }}"
        data-done-label="{{ __('sub-goals.table.done') }}"
        data-not-done-label="{{ __('sub-goals.table.not_done') }}"
        data-summary-template="{{ __('sub-goals.summary', ['done' => ':done', 'total' => ':total', 'amount' => ':amount']) }}"
    ></div>

    <div id="sub-goals-locked-notice" class="mb-6 hidden rounded-lg border border-outline-variant bg-surface-container-low px-4 py-3 font-body text-sm text-on-surface-variant">{{ __('goals.locked') }}</div>
    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>
    <div data-status-error role="alert" class="mb-6 hidden rounded-lg border border-error/20 bg-error/10 px-4 py-3 font-body text-sm text-error">{{ __('sub-goals.flash.status_failed') }}</div>

    <p class="mb-4 hidden font-body text-body-md font-medium text-on-surface" data-sub-goals-summary></p>

    <x-molecules.table-toolbar
        id="sub-goals-table-toolbar"
        :search-placeholder="__('sub-goals.table.search')"
        :per-page-label="__('sub-goals.table.per_page')"
    />

    <x-organisms.table-mobile-bar
        id="sub-goals-table-mobile-bar"
        :headers="$tableHeaders"
        :search-placeholder="__('sub-goals.table.search')"
        :per-page-label="__('sub-goals.table.per_page')"
    />

    <div id="sub-goals-table-wrapper" class="hidden max-sm:pb-20">
        <x-organisms.table
            id="sub-goals-table"
            :selectable="false"
            :headers="$tableHeaders"
            :config="[
                'labels' => [
                    'empty' => __('sub-goals.table.empty'),
                    'expand' => __('sub-goals.table.show_more'),
                    'collapse' => __('sub-goals.table.show_less'),
                    'view' => __('sub-goals.actions.view'),
                    'edit' => __('sub-goals.actions.edit'),
                ],
            ]"
        />

        <x-molecules.pagination data-pagination />
    </div>

@endsection

@push('scripts')
    @vite('resources/js/sub-goals/index.js')
@endpush
