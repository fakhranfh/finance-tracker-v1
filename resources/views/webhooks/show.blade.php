@extends('layouts.dashboard')

@section('title', __('webhooks.show.title'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('webhooks.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('webhooks.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface" data-webhook-name>
                {{ __('webhooks.show.title') }}
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-atoms.button href="{{ route('webhooks.logs', ['id' => $id, 'from' => 'show']) }}" variant="secondary">
                {{ __('webhooks.logs.title') }}
            </x-atoms.button>
            <x-atoms.button href="{{ route('webhooks.test', ['id' => $id, 'from' => 'show']) }}" variant="secondary">
                {{ __('webhooks.test.title') }}
            </x-atoms.button>
            <x-atoms.button href="{{ route('webhooks.edit', $id) }}">
                {{ __('webhooks.actions.edit') }}
            </x-atoms.button>
        </div>
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        <x-organisms.webhook-show-skeleton id="webhook-show-skeleton" />

        <div
            id="webhook-details"
            class="hidden space-y-5"
            data-webhook-id="{{ $id }}"
            data-all-label="{{ __('webhooks.show.all') }}"
            data-active-label="{{ __('webhooks.show.active') }}"
            data-inactive-label="{{ __('webhooks.show.inactive') }}"
            data-yes-label="{{ __('webhooks.show.yes') }}"
            data-no-label="{{ __('webhooks.show.no') }}"
            @foreach ($recordTypes as $value => $label) data-type-{{ $value }}="{{ $label }}" @endforeach
        >
            <x-molecules.copy-field name="token" :label="__('webhooks.fields.token')" :copy-label="__('webhooks.actions.copy')" :copied-label="__('webhooks.actions.copied')" />
            <x-atoms.button type="button" id="rotate-webhook-token" variant="secondary">
                {{ __('webhooks.actions.rotate_token') }}
            </x-atoms.button>
            <x-molecules.copy-field name="url" :label="__('webhooks.fields.url')" :copy-label="__('webhooks.actions.copy')" :copied-label="__('webhooks.actions.copied')" />

            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                @foreach ($fields as $field => $label)
                    <div>
                        <dt class="font-body text-body-sm font-medium text-on-surface-variant">{{ $label }}</dt>
                        <dd class="mt-1 break-words font-body text-body-md text-on-surface" data-field="{{ $field }}"></dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    <x-organisms.modal id="rotate-webhook-token-modal" :title="__('webhooks.actions.confirm_rotate_token')">
        <div class="flex justify-end gap-3">
            <x-atoms.button type="button" variant="secondary" data-modal-close>
                {{ __('webhooks.actions.cancel') }}
            </x-atoms.button>
            <x-atoms.button type="button" id="confirm-rotate-webhook-token" variant="danger">
                {{ __('webhooks.actions.rotate_token') }}
            </x-atoms.button>
        </div>
    </x-organisms.modal>
@endsection

@push('scripts')
    @vite('resources/js/webhooks/show.js')
@endpush
