@extends('layouts.dashboard')

@section('title', __('cycles.detail.title'))

@section('content')
    <div
        id="cycle-show-page"
        data-group="{{ $group }}"
        data-type="{{ $type }}"
        data-id="{{ $id }}"
        data-labels="{{ json_encode($labels) }}"
    >
        <header class="mb-6 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <a id="cycles-back" href="#" class="inline-flex size-11 shrink-0 items-center justify-center rounded-full text-on-surface hover:bg-primary-fixed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" aria-label="{{ __('cycles.back') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </a>
                <h1 class="font-heading text-headline-md font-bold text-on-surface">{{ __('cycles.detail.title') }}</h1>
            </div>

            <div id="cycle-menu-wrap" class="relative hidden">
                <button type="button" id="cycle-menu-toggle" class="inline-flex size-11 cursor-pointer items-center justify-center rounded-full text-on-surface hover:bg-primary-fixed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" aria-haspopup="menu" aria-expanded="false" aria-controls="cycle-menu" aria-label="{{ __('cycles.detail.menu') }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-5" aria-hidden="true">
                        <path d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </button>
                <div id="cycle-menu" role="menu" class="absolute right-0 top-12 z-20 hidden w-52 rounded-lg border border-outline-variant bg-surface py-1">
                    <button type="button" role="menuitem" data-menu-action="edit" class="flex min-h-11 w-full cursor-pointer items-center px-4 text-left font-body text-body-md text-on-surface hover:bg-surface-container-low">{{ __('cycles.detail.edit') }}</button>
                    <button type="button" role="menuitem" data-menu-action="finish" class="flex min-h-11 w-full cursor-pointer items-center px-4 text-left font-body text-body-md text-on-surface hover:bg-surface-container-low">{{ __('cycles.detail.finish') }}</button>
                    <button type="button" role="menuitem" data-menu-action="delete" class="flex min-h-11 w-full cursor-pointer items-center px-4 text-left font-body text-body-md text-error hover:bg-error/10">{{ __('cycles.detail.delete') }}</button>
                </div>
            </div>
        </header>

        <div data-flash-success role="status" class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>
        <div data-flash-error role="alert" class="mb-6 hidden rounded-lg border border-error/20 bg-error/10 px-4 py-3 font-body text-sm text-error"></div>

        <x-organisms.cycle-show-skeleton id="cycle-show-skeleton" />

        <div id="cycle-show" class="hidden">
            <section class="rounded-lg border border-outline-variant bg-surface p-5">
                <h2 class="font-heading text-headline-sm font-semibold text-on-surface" data-field="title"></h2>
                <dl>
                    <div class="mt-4 border-t border-outline-variant pt-4">
                        <dt class="sr-only">{{ __('cycles.detail.price') }}</dt>
                        <dd class="font-body text-body-md text-on-surface">{{ __('cycles.detail.price') }}: <span data-field="price"></span></dd>
                    </div>
                    <div class="mt-4 border-t border-outline-variant pt-4">
                        <dt class="sr-only">{{ __('cycles.detail.usage') }}</dt>
                        <dd class="font-body text-body-md text-on-surface">{{ __('cycles.detail.usage') }}: <span data-field="usage"></span></dd>
                    </div>
                </dl>
            </section>

            <section class="mt-4 rounded-lg border border-outline-variant bg-surface p-3">
                <p class="rounded-md bg-success/10 px-4 py-3 font-body text-body-md text-on-surface" data-field="estimate"></p>
            </section>
        </div>

        <p id="cycle-show-missing" class="hidden font-body text-body-md text-error">{{ __('cycles.js.not_found') }}</p>
    </div>

    <x-organisms.cycle-entry-modals />
@endsection

@push('scripts')
    @vite('resources/js/cycles/show.js')
@endpush
