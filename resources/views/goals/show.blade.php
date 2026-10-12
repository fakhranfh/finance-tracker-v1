@extends('layouts.dashboard')

@section('title', __('goals.show'))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('goals.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('goals.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ __('goals.show') }}
            </h1>
        </div>

        <x-atoms.button href="{{ route('goals.edit', $id) }}" id="goal-edit-link" variant="secondary">
            {{ __('goals.actions.edit') }}
        </x-atoms.button>
    </div>

    <div id="goal-locked-notice" class="mb-6 hidden rounded-lg border border-outline-variant bg-surface-container-low px-4 py-3 font-body text-sm text-on-surface-variant">{{ __('goals.locked') }}</div>
    <div data-status-error role="alert" class="mb-6 hidden rounded-lg border border-error/20 bg-error/10 px-4 py-3 font-body text-sm text-error">{{ __('goals.flash.status_failed') }}</div>
    <div data-flash-success class="mb-6 hidden rounded-lg border border-success/20 bg-success/10 px-4 py-3 font-body text-sm text-success"></div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        <x-organisms.goal-show-skeleton id="goal-show-skeleton" />

        <div
            id="goal-show"
            class="hidden"
            data-item-id="{{ $id }}"
            data-icons="{{ json_encode($icons) }}"
            data-status-labels="{{ json_encode($statusLabels) }}"
            data-estimate-reached="{{ __('goals.show_page.estimate_reached') }}"
            data-estimate-unknown="{{ __('goals.show_page.estimate_unknown') }}"
            data-reached-set="{{ __('goals.flash.reached_set') }}"
            data-deleted="{{ __('goals.flash.deleted') }}"
            data-saved-added="{{ __('goals.flash.saved_added') }}"
        >
            <div class="mb-6 flex items-center gap-3">
                <span data-field="icon"></span>
                <h2 class="font-heading text-title-lg font-bold text-on-surface" data-field="name"></h2>
            </div>

            <div class="relative mx-auto mb-8 size-56">
                <svg class="size-full -rotate-90" viewBox="0 0 100 100" fill="none" stroke-width="8" aria-hidden="true">
                    <circle cx="50" cy="50" r="45" class="stroke-secondary-container" />
                    <circle cx="50" cy="50" r="45" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100" stroke-linecap="round" class="stroke-primary" data-field="progress" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center px-8 text-center">
                    <span class="font-heading text-headline-md font-bold text-primary" data-field="progress_label"></span>
                    <span class="font-body text-body-md font-medium text-on-surface" data-field="saved_amount"></span>
                    <span class="font-body text-body-sm text-on-surface-variant">{{ __('goals.show_page.of') }} <span data-field="target_amount"></span></span>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                @foreach ($fields as $field)
                    <div @class(['sm:col-span-2' => $field === 'note'])>
                        <dt class="font-body text-body-sm text-on-surface-variant">{{ __("goals.show_page.{$field}") }}</dt>
                        <dd class="mt-1 font-body text-body-md text-on-surface" data-field="{{ $field }}"></dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-outline-variant pt-6">
                <x-atoms.button type="button" id="delete-goal-open" variant="danger">
                    {{ __('goals.actions.delete') }}
                </x-atoms.button>

                <div class="flex flex-wrap justify-end gap-3">
                    <x-atoms.button type="button" id="set-reached-open" variant="secondary">
                        {{ __('goals.actions.set_reached') }}
                    </x-atoms.button>
                    <x-atoms.button type="button" id="add-saved-open">
                        {{ __('goals.actions.add_saved') }}
                    </x-atoms.button>
                </div>
            </div>
        </div>

        <p id="goal-show-missing" class="hidden font-body text-body-md text-error">{{ __('goals.show_page.not_found') }}</p>
    </div>

    <x-organisms.modal id="set-reached-modal" :title="__('goals.actions.set_reached_title')">
        <p class="mb-6 font-body text-body-md text-on-surface-variant">{{ __('goals.actions.set_reached_warning') }}</p>
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('goals.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-set-reached">
                {{ __('goals.actions.set_reached_confirm') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

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

    <x-organisms.modal id="add-saved-modal" :title="__('goals.actions.add_saved_title')">
        <form id="add-saved-form" novalidate class="space-y-5" data-invalid-message="{{ __('goals.actions.add_saved_invalid') }}">
            <x-molecules.form-group name="amount" type="text" inputmode="decimal" :label="__('goals.actions.add_saved_amount')" data-money required />

            <div class="flex justify-end gap-3">
                <x-atoms.button type="button" variant="secondary" data-modal-close>
                    {{ __('goals.actions.cancel') }}
                </x-atoms.button>
                <x-atoms.button type="submit">
                    {{ __('goals.actions.save') }}
                </x-atoms.button>
            </div>
        </form>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/goals/show.js')
@endpush
