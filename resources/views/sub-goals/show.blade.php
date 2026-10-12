@extends('layouts.dashboard')

@section('title', __('sub-goals.show'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('goals.sub-goals.index', $goalId) }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('sub-goals.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('sub-goals.show') }}
            </h1>
        </div>

        <x-atoms.button href="{{ route('goals.sub-goals.edit-item', [$goalId, $id]) }}" id="sub-goal-edit-link" variant="secondary">
            {{ __('sub-goals.edit_one') }}
        </x-atoms.button>
    </div>

    <div id="sub-goal-locked-notice" class="mb-6 hidden rounded-lg border border-outline-variant bg-surface-container-low px-4 py-3 font-body text-sm text-on-surface-variant">{{ __('goals.locked') }}</div>
    <div data-status-error role="alert" class="mb-6 hidden rounded-lg border border-error/20 bg-error/10 px-4 py-3 font-body text-sm text-error">{{ __('sub-goals.flash.status_failed') }}</div>
    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        <x-organisms.sub-goal-show-skeleton id="sub-goal-show-skeleton" />

        <div
            id="sub-goal-show"
            class="hidden"
            data-goal-id="{{ $goalId }}"
            data-item-id="{{ $id }}"
            data-done-label="{{ __('sub-goals.table.done') }}"
            data-not-done-label="{{ __('sub-goals.table.not_done') }}"
            data-marked-done="{{ __('sub-goals.flash.marked_done') }}"
            data-marked-not-done="{{ __('sub-goals.flash.marked_not_done') }}"
        >
            <h2 class="mb-6 font-heading text-title-lg font-bold text-on-surface" data-field="name"></h2>

            <div class="relative mx-auto mb-8 size-56">
                <svg class="size-full -rotate-90" viewBox="0 0 100 100" fill="none" stroke-width="8" aria-hidden="true">
                    <circle cx="50" cy="50" r="45" class="stroke-secondary-container" />
                    <circle cx="50" cy="50" r="45" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100" stroke-linecap="round" class="stroke-primary" data-field="progress" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center px-8 text-center">
                    <span class="font-heading text-headline-md font-bold text-primary" data-field="progress_label"></span>
                    <span class="font-body text-body-md font-medium text-on-surface" data-field="saved_amount_ring"></span>
                    <span class="font-body text-body-sm text-on-surface-variant">{{ __('goals.show_page.of') }} <span data-field="target_amount_ring"></span></span>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                @foreach ($fields as $field)
                    <div @class(['sm:col-span-2' => $field === 'note'])>
                        <dt class="font-body text-body-sm text-on-surface-variant">{{ __("sub-goals.show_page.{$field}") }}</dt>
                        <dd class="mt-1 font-body text-body-md text-on-surface" data-field="{{ $field }}"></dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-6 border-t border-outline-variant pt-6">
                <h3 class="font-heading text-title-md font-bold text-on-surface">{{ __('sub-goals.show_page.history_title') }}</h3>

                <x-molecules.per-page-select :label="__('goals.table.per_page')" class="mt-3 justify-end max-sm:hidden" />

                <div id="sub-goal-savings-wrapper" class="mt-3 max-sm:pb-20">
                    <x-organisms.table
                        id="sub-goal-savings-table"
                        :selectable="false"
                        :headers="$savingHeaders"
                        :config="[
                            'labels' => [
                                'empty' => __('sub-goals.show_page.history_empty'),
                                'expand' => __('sub-goals.table.show_more'),
                                'collapse' => __('sub-goals.table.show_less'),
                            ],
                        ]"
                    />

                    <x-molecules.pagination data-pagination />
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-outline-variant pt-6">
                <x-atoms.button type="button" id="toggle-done" variant="secondary" data-done-label="{{ __('sub-goals.show_page.mark_done') }}" data-not-done-label="{{ __('sub-goals.show_page.mark_not_done') }}">
                    {{ __('sub-goals.show_page.mark_done') }}
                </x-atoms.button>
            </div>
        </div>

        <p id="sub-goal-show-missing" class="hidden font-body text-body-md text-error">{{ __('sub-goals.show_page.not_found') }}</p>
    </div>

    <x-organisms.table-mobile-bar
        id="sub-goal-savings-mobile-bar"
        :headers="$savingHeaders"
        :per-page-label="__('goals.table.per_page')"
        :searchable="false"
        :hidden="true"
    />
@endsection

@push('scripts')
    @vite('resources/js/sub-goals/show.js')
@endpush
