@extends('layouts.dashboard')

@section('title', ($id ? __('webhooks.edit') : __('webhooks.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('webhooks.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('webhooks.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('webhooks.edit') : __('webhooks.create') }}
            </h1>
        </div>

        <div class="flex items-center gap-3">
            @if ($id)
                <x-atoms.button href="{{ route('webhooks.logs', ['id' => $id, 'from' => 'edit']) }}" variant="secondary">
                    {{ __('webhooks.logs.title') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('webhooks.api-docs', ['webhook' => $id]) }}" variant="secondary">
                    {{ __('webhooks.test.title') }}
                </x-atoms.button>
            @endif
            @if (app()->isLocal())
                <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                    {{ __('webhooks.actions.autofill') }}
                </x-atoms.button>
            @endif
        </div>
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($id)
            <x-organisms.webhook-form-skeleton id="webhook-form-skeleton" />
        @endif

        <form
            id="webhook-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            data-pick-one-message="{{ __('webhooks.errors.pick_one') }}"
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('webhooks.flash.updated') : __('webhooks.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('webhooks.fields.name')" required autofocus />
            @if ($id)
                <x-molecules.copy-field name="token" :label="__('webhooks.fields.token')" :copy-label="__('webhooks.actions.copy')" :copied-label="__('webhooks.actions.copied')" />
                <x-molecules.copy-field name="url" :label="__('webhooks.fields.url')" :copy-label="__('webhooks.actions.copy')" :copied-label="__('webhooks.actions.copied')" />
            @endif
            <div class="space-y-2" id="allowed_ips" data-ip-required-message="{{ __('webhooks.errors.ip_required') }}">
                <x-atoms.label>{{ __('webhooks.fields.allowed_ips') }}</x-atoms.label>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                        <x-atoms.radio name="ip_mode" id="ip_mode_all" value="all" checked />
                        {{ __('webhooks.fields.ip_mode_all') }}
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                        <x-atoms.radio name="ip_mode" id="ip_mode_only" value="only" />
                        {{ __('webhooks.fields.ip_mode_only') }}
                    </label>
                </div>
                <div id="allowed-ips-panel" class="hidden space-y-2">
                    <div id="allowed-ips-list" class="space-y-2"></div>
                    <div class="flex items-center gap-4">
                        <button type="button" class="cursor-pointer font-body text-body-sm font-medium text-primary hover:underline" data-ip-add>
                            {{ __('webhooks.actions.add_ip') }}
                        </button>
                        <button type="button" class="hidden cursor-pointer font-body text-body-sm font-medium text-error hover:underline" data-ip-clear>
                            {{ __('webhooks.actions.delete_all') }}
                        </button>
                    </div>
                </div>
                <template id="allowed-ip-row-template">
                    <div class="flex items-center gap-2" data-ip-row>
                        <x-atoms.input type="text" name="allowed_ip[]" id="" spellcheck="false" placeholder="203.0.113.5 / 198.51.100.0/24" aria-label="{{ __('webhooks.fields.allowed_ips') }}" data-ip-input />
                        <button type="button" class="shrink-0 cursor-pointer font-body text-body-sm font-medium text-error hover:underline" data-ip-delete>
                            {{ __('webhooks.actions.delete') }}
                        </button>
                    </div>
                </template>
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('webhooks.fields.allowed_ips_hint') }}</p>
                <x-atoms.form-error for="allowed_ips" />
            </div>
            <div class="space-y-1.5">
                <x-atoms.label for="allowed_account_ids">{{ __('webhooks.fields.allowed_account_ids') }}</x-atoms.label>
                <div>
                    <button
                        type="button"
                        class="hidden cursor-pointer font-body text-body-sm font-medium text-primary hover:underline"
                        data-toggle-all="allowed_account_ids"
                        data-select-label="{{ __('webhooks.actions.select_all') }}"
                        data-deselect-label="{{ __('webhooks.actions.deselect_all') }}"
                    >
                        {{ __('webhooks.actions.deselect_all') }}
                    </button>
                </div>
                <div id="allowed_account_ids" class="grid grid-cols-1 gap-2 sm:grid-cols-2" data-allowed-accounts data-empty-label="{{ __('webhooks.fields.no_accounts') }}" data-error-label="{{ __('webhooks.fields.accounts_failed') }}">
                    <x-organisms.webhook-accounts-skeleton />
                </div>
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('webhooks.fields.allowed_account_ids_hint') }}</p>
                <x-atoms.form-error for="allowed_account_ids" />
            </div>
            <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                    <x-atoms.checkbox name="can_create_accounts" id="can_create_accounts" />
                    <x-atoms.label for="can_create_accounts">{{ __('webhooks.fields.can_create_accounts') }}</x-atoms.label>
                </div>
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('webhooks.fields.can_create_accounts_hint') }}</p>
                <x-atoms.form-error for="can_create_accounts" />
            </div>
            <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                    <x-atoms.checkbox name="can_create_categories" id="can_create_categories" />
                    <x-atoms.label for="can_create_categories">{{ __('webhooks.fields.can_create_categories') }}</x-atoms.label>
                </div>
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('webhooks.fields.can_create_categories_hint') }}</p>
                <x-atoms.form-error for="can_create_categories" />
            </div>
            <div class="space-y-1.5">
                <x-atoms.label for="allowed_types">{{ __('webhooks.fields.allowed_types') }}</x-atoms.label>
                <div>
                    <button
                        type="button"
                        class="hidden cursor-pointer font-body text-body-sm font-medium text-primary hover:underline"
                        data-toggle-all="allowed_types"
                        data-select-label="{{ __('webhooks.actions.select_all') }}"
                        data-deselect-label="{{ __('webhooks.actions.deselect_all') }}"
                    >
                        {{ __('webhooks.actions.deselect_all') }}
                    </button>
                </div>
                <div id="allowed_types" class="flex flex-wrap gap-x-6 gap-y-2">
                    @foreach ($recordTypes as $value => $label)
                        <label class="flex cursor-pointer items-center gap-2 font-body text-body-md text-on-surface">
                            <x-atoms.checkbox name="allowed_types[]" id="allowed_type_{{ $value }}" value="{{ $value }}" :checked="! $id" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('webhooks.fields.allowed_types_hint') }}</p>
                <x-atoms.form-error for="allowed_types" />
            </div>
            <div class="flex items-center gap-2">
                <x-atoms.checkbox name="is_active" id="is_active" />
                <x-atoms.label for="is_active">{{ __('webhooks.fields.is_active') }}</x-atoms.label>
            </div>

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('webhooks.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('webhooks.index') }}" variant="secondary">
                    {{ __('webhooks.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/webhooks/form.js')
@endpush
