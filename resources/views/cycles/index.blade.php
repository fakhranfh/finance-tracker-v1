@extends('layouts.dashboard')

@section('title', __('cycles.title'))

@section('content')
    <div
        id="cycles-page"
        data-level="{{ $level }}"
        data-group="{{ $params['group'] ?? '' }}"
        data-type="{{ $params['type'] ?? '' }}"
        data-default-icon="{{ $defaultIcon }}"
        data-labels="{{ json_encode($labels) }}"
        data-icons="{{ json_encode($icons) }}"
    >
        @if (app()->isLocal())
            <div class="mb-4 grid w-full grid-cols-[6rem_1fr] items-center gap-3 sm:flex sm:justify-end">
                <x-atoms.input
                    type="number"
                    name="generate-count"
                    id="generate-count"
                    value="{{ $generateCount }}"
                    min="1"
                    max="50"
                    class="!w-24"
                    aria-label="{{ __('cycles.generate.count') }}"
                    title="{{ __('cycles.generate.count') }}"
                />
                <x-atoms.button type="button" id="generate-cycles" variant="secondary" data-success-message="{{ __('cycles.generate.flash') }}">
                    {{ __('cycles.generate.button') }}
                </x-atoms.button>
            </div>
        @endif

        <header class="mb-6">
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <a id="cycles-back" href="#" class="hidden size-11 shrink-0 items-center justify-center rounded-full text-on-surface hover:bg-primary-fixed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" aria-label="{{ __('cycles.back') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </a>
                @if ($level === 'groups')
                    <h1 class="font-heading text-headline-md font-bold text-on-surface" data-title>{{ __('cycles.title') }}</h1>
                    <x-atoms.button type="button" id="cycles-add-category" class="ml-auto">
                        {{ __('cycles.form.add_group') }}
                    </x-atoms.button>
                @else
                    <h1 class="font-heading text-headline-md font-bold text-on-surface" data-title></h1>
                    <x-atoms.skeleton class="h-8 w-48" data-title-skeleton />
                @endif
            </div>

            <div class="relative">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="pointer-events-none absolute inset-y-0 left-4 my-auto size-5 text-outline" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <x-atoms.input type="search" name="search" id="cycles-search" class="!pl-12" :placeholder="__('cycles.search')" :aria-label="__('cycles.search')" autocomplete="off" />
            </div>
        </header>

        <div data-flash-success role="status" class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>
        <div data-flash-error role="alert" class="mb-6 hidden rounded-lg border border-error/20 bg-error/10 px-4 py-3 font-body text-sm text-error"></div>

        @if ($level === 'entries')
            <x-organisms.cycles-timeline-skeleton id="cycles-skeleton" />
        @else
            <x-organisms.cycles-tiles-skeleton id="cycles-skeleton" />
        @endif

        <div id="cycles-content" class="hidden pb-24">
            <div class="mb-6 rounded-lg border border-outline-variant bg-surface p-5">
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('cycles.summary.monthly') }}</p>
                <p class="mt-1 font-heading text-headline-md font-bold text-primary" data-summary="monthly"></p>
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('cycles.summary.hint') }}</p>
            </div>

            <div data-pager-bar class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <x-molecules.per-page-select :label="__('cycles.per_page')" :options="$perPageOptions" />
                <x-molecules.pagination data-pagination class="!mt-0" />
            </div>

            <div id="cycles-select-bar" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-outline-variant bg-surface px-4 py-2">
                <label class="flex min-h-11 cursor-pointer items-center gap-3 font-body text-body-md text-on-surface">
                    <input type="checkbox" id="cycles-select-all" class="size-5 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    {{ __('cycles.entries.select_all') }}
                </label>
                <div class="flex items-center gap-3">
                    <span id="cycles-selected-count" class="hidden font-body text-body-sm text-on-surface-variant" data-template="{{ __('cycles.entries.selected', ['n' => ':n']) }}"></span>
                    <x-atoms.button type="button" id="cycles-bulk-delete" variant="danger" class="hidden">
                        {{ __('cycles.entries.delete_selected') }}
                    </x-atoms.button>
                </div>
            </div>

            @if ($level === 'entries')
                <ol id="cycles-timeline" class="space-y-4"></ol>
            @else
                <div id="cycles-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
            @endif

            <p id="cycles-empty" class="mt-6 hidden rounded-lg border border-dashed border-outline-variant bg-surface px-6 py-10 text-center font-body text-body-md text-on-surface-variant"></p>

            <div data-pager-bar class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <x-molecules.per-page-select :label="__('cycles.per_page')" :options="$perPageOptions" />
                <x-molecules.pagination data-pagination class="!mt-0" />
            </div>
        </div>

        <p id="cycles-missing" class="hidden font-body text-body-md text-error">{{ __('cycles.js.not_found') }}</p>

        @if ($level === 'entries')
            <div class="fixed bottom-6 right-6 z-30 flex flex-col gap-3">
                <button type="button" id="cycle-fab-add" class="flex size-14 cursor-pointer items-center justify-center rounded-full bg-primary text-on-primary shadow-sm hover:bg-primary-container focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" aria-label="{{ __('cycles.entries.add') }}" title="{{ __('cycles.entries.add') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <x-organisms.cycle-templates />

    @if ($level === 'entries')
        <x-organisms.cycle-entry-modals />
    @else
        <x-organisms.modal id="cycle-tile-modal" :title="$tileTitle">
            <form id="cycle-tile-form" novalidate class="space-y-5" data-add-title="{{ $tileTitle }}" data-edit-title="{{ $tileCopy['edit_title'] }}">
                <x-molecules.form-group name="tile_name" type="text" :label="__('cycles.form.tile_name')" maxlength="40" required />

                <fieldset class="space-y-2">
                    <legend class="font-body text-label-md font-medium text-on-surface-variant">{{ __('cycles.form.icon') }}</legend>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($icons as $icon => $path)
                            <label class="cursor-pointer">
                                <input type="radio" name="icon" value="{{ $icon }}" class="peer sr-only" @checked($icon === $defaultIcon)>
                                <span class="flex size-11 items-center justify-center rounded-lg border border-outline-variant text-on-surface peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary peer-focus-visible:ring-2" title="{{ $iconLabels[$icon] }}">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">{!! $path !!}</svg>
                                    <span class="sr-only">{{ $iconLabels[$icon] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-atoms.form-error for="icon" />
                </fieldset>

                <div class="flex justify-end gap-3">
                    <x-atoms.button type="button" variant="secondary" data-modal-close>
                        {{ __('cycles.form.cancel') }}
                    </x-atoms.button>
                    <x-atoms.button type="submit">
                        {{ __('cycles.form.save') }}
                    </x-atoms.button>
                </div>
            </form>
        </x-organisms.modal>

        <x-organisms.modal id="cycle-tile-delete-modal" :title="$tileCopy['delete_title']">
            <p class="mb-6 font-body text-body-md text-on-surface-variant">{{ $tileCopy['delete_text'] }}</p>
            <div class="flex justify-end gap-3">
                <x-atoms.button type="button" variant="secondary" data-modal-close>
                    {{ __('cycles.form.cancel') }}
                </x-atoms.button>
                <x-atoms.button type="button" id="confirm-delete-tile" variant="danger">
                    {{ __('cycles.form.delete') }}
                </x-atoms.button>
            </div>
        </x-organisms.modal>

        <x-organisms.modal id="cycle-tile-bulk-delete-modal" :title="$tileCopy['bulk_title']">
            <p class="mb-6 font-body text-body-md text-on-surface-variant">{{ $tileCopy['bulk_text'] }}</p>
            <div class="flex justify-end gap-3">
                <x-atoms.button type="button" variant="secondary" data-modal-close>
                    {{ __('cycles.form.cancel') }}
                </x-atoms.button>
                <x-atoms.button type="button" id="confirm-bulk-delete-tiles" variant="danger">
                    {{ __('cycles.form.delete') }}
                </x-atoms.button>
            </div>
        </x-organisms.modal>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/cycles/index.js')
@endpush
