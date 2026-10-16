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
                <x-atoms.button href="{{ route('webhooks.logs', $id) }}" variant="secondary">
                    {{ __('webhooks.logs.title') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('webhooks.test', $id) }}" variant="secondary">
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
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('webhooks.flash.updated') : __('webhooks.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('webhooks.fields.name')" required autofocus />
            @if ($id)
                <x-molecules.copy-field name="token" :label="__('webhooks.fields.token')" :copy-label="__('webhooks.actions.copy')" :copied-label="__('webhooks.actions.copied')" />
                <x-molecules.copy-field name="url" :label="__('webhooks.fields.url')" :copy-label="__('webhooks.actions.copy')" :copied-label="__('webhooks.actions.copied')" />
            @endif
            <div class="space-y-1.5">
                <x-atoms.label for="allowed_ips">{{ __('webhooks.fields.allowed_ips') }}</x-atoms.label>
                <x-atoms.textarea name="allowed_ips" rows="3" spellcheck="false" placeholder="203.0.113.5&#10;198.51.100.0/24" />
                <p class="font-body text-body-sm text-on-surface-variant">{{ __('webhooks.fields.allowed_ips_hint') }}</p>
                <x-atoms.form-error for="allowed_ips" />
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
