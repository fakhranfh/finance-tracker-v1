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
            data-estimate-reached="{{ __('goals.show_page.estimate_reached') }}"
            data-estimate-unknown="{{ __('goals.show_page.estimate_unknown') }}"
            data-reached-set="{{ __('goals.flash.reached_set') }}"
            data-deleted="{{ __('goals.flash.deleted') }}"
            data-saved-added="{{ __('goals.flash.saved_added') }}"
            data-saving-updated="{{ __('goals.flash.saving_updated') }}"
            data-saving-deleted="{{ __('goals.flash.saving_deleted') }}"
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

            <div
                class="mt-6 border-t border-outline-variant pt-6"
                id="goal-sub-goals"
                data-summary-template="{{ __('sub-goals.summary', ['done' => ':done', 'total' => ':total', 'amount' => ':amount']) }}"
                data-empty="{{ __('sub-goals.show_page.empty') }}"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-heading text-title-md font-bold text-on-surface">{{ __('sub-goals.show_page.title') }}</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-atoms.button href="{{ route('goals.sub-goals.index', $id) }}" id="manage-sub-goals-link" variant="secondary">
                            {{ __('sub-goals.show_page.manage') }}
                        </x-atoms.button>
                    </div>
                </div>

                <p class="mt-3 font-body text-body-md text-on-surface-variant" data-sub-goals-summary></p>
            </div>

            <div class="mt-6 border-t border-outline-variant pt-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-heading text-title-md font-bold text-on-surface">{{ __('goals.history.title') }}</h3>
                    @if (app()->isLocal())
                        <div id="generate-savings-controls" class="flex items-center gap-3">
                            <x-atoms.input
                                type="number"
                                name="generate-savings-count"
                                id="generate-savings-count"
                                value="5"
                                min="1"
                                max="50"
                                class="!w-24"
                                aria-label="{{ __('goals.history.generate_count') }}"
                                title="{{ __('goals.history.generate_count') }}"
                            />
                            <x-atoms.button type="button" id="generate-savings" variant="secondary" data-success-message="{{ __('goals.flash.generated_savings') }}">
                                {{ __('goals.actions.generate') }}
                            </x-atoms.button>
                        </div>
                    @endif
                </div>

                <x-molecules.per-page-select :label="__('goals.table.per_page')" class="mt-3 justify-end max-sm:hidden" />

                <div id="goal-savings-wrapper" class="mt-3 max-sm:pb-20 [&[data-no-sub-goals]_td:nth-child(4)]:hidden [&[data-no-sub-goals]_th:nth-child(4)]:hidden">
                    <x-organisms.table
                        id="goal-savings-table"
                        :headers="$savingHeaders"
                        :filters="$savingFilters"
                        filters-id="goal-savings-filters"
                        :config="[
                            'labels' => [
                                'bulkDelete' => __('goals.actions.delete_selected'),
                                'selected' => __('goals.table.selected'),
                                'selectAllMatching' => __('goals.table.select_all_matching', ['total' => ':total']),
                                'allSelected' => __('goals.table.all_selected', ['total' => ':total']),
                                'clearSelection' => __('goals.table.clear_selection'),
                                'empty' => __('goals.history.empty'),
                                'clearFilters' => __('goals.history.clear_filters'),
                                'expand' => __('goals.table.show_more'),
                                'collapse' => __('goals.table.show_less'),
                                'edit' => __('goals.actions.edit'),
                                'delete' => __('goals.actions.delete'),
                            ],
                        ]"
                    />

                    <x-molecules.pagination data-pagination />
                </div>
            </div>

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

    <x-organisms.table-mobile-bar
        id="goal-savings-mobile-bar"
        :headers="$savingHeaders"
        :per-page-label="__('goals.table.per_page')"
        :searchable="false"
        :hidden="true"
    />

    <x-organisms.modal id="delete-saving-modal" :title="__('goals.history.confirm_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('goals.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-delete-saving" variant="danger">
                {{ __('goals.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="bulk-delete-savings-modal" :title="__('goals.history.confirm_bulk_delete')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('goals.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-bulk-delete-savings" variant="danger">
                {{ __('goals.actions.delete') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>

    <x-organisms.modal id="edit-saving-modal" :title="__('goals.history.edit_title')">
        <form id="edit-saving-form" novalidate class="space-y-5" data-invalid-message="{{ __('goals.actions.add_saved_invalid') }}">
            <x-molecules.form-group name="saving_amount" type="text" inputmode="decimal" :label="__('goals.history.amount')" data-money required />

            <div class="hidden space-y-1.5" data-sub-goal-field>
                <x-atoms.label for="saving_amount_sub_goal">{{ __('goals.history.sub_goal') }}</x-atoms.label>
                <x-atoms.select name="sub_goal_id" id="saving_amount_sub_goal">
                    <option value="">{{ __('goals.history.no_sub_goal') }}</option>
                </x-atoms.select>
                <x-atoms.form-error for="sub_goal_id" />
            </div>

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

    <x-organisms.modal id="add-saved-modal" :title="__('goals.actions.add_saved_title')">
        <form id="add-saved-form" novalidate class="space-y-5" data-invalid-message="{{ __('goals.actions.add_saved_invalid') }}">
            <x-molecules.form-group name="amount" type="text" inputmode="decimal" :label="__('goals.actions.add_saved_amount')" data-money required />

            <div class="hidden space-y-1.5" data-sub-goal-field>
                <x-atoms.label for="amount_sub_goal">{{ __('goals.history.sub_goal') }}</x-atoms.label>
                <x-atoms.select name="sub_goal_id" id="amount_sub_goal">
                    <option value="">{{ __('goals.history.no_sub_goal') }}</option>
                </x-atoms.select>
                <x-atoms.form-error for="sub_goal_id" />
            </div>

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
