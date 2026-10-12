@extends('layouts.dashboard')

@section('title', ($id ? __('goals.edit') : __('goals.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('goals.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('goals.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('goals.edit') : __('goals.create') }}
            </h1>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('goals.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($id)
            <x-organisms.goal-form-skeleton id="goal-form-skeleton" />
        @endif

        <form
            id="goal-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('goals.flash.updated') : __('goals.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('goals.fields.name')" required autofocus />
            <x-molecules.form-group name="target_amount" type="number" :label="__('goals.fields.target_amount')" step="0.01" min="0.01" data-calculator required />
            <x-molecules.form-group name="saved_amount" type="number" :label="__('goals.fields.saved_amount')" step="0.01" min="0" value="0" data-calculator required />
            <x-molecules.form-group name="desired_date" type="date" :label="__('goals.fields.desired_date')" required />

            <fieldset class="space-y-2">
                <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('goals.fields.color') }}</legend>
                <div class="flex items-center gap-3">
                    <input
                        type="color"
                        name="color"
                        value="{{ config('goal.default_color') }}"
                        class="h-10 w-14 cursor-pointer rounded-md border border-outline-variant bg-surface p-1"
                        data-color-hex
                    >
                    <span class="font-body text-body-sm text-on-surface-variant" data-color-hex-label>{{ config('goal.default_color') }}</span>
                </div>
                <x-atoms.form-error for="color" />
            </fieldset>

            <fieldset class="space-y-2">
                <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('goals.fields.icon') }}</legend>
                <div class="flex flex-wrap gap-3">
                    @foreach (config('goal.icons') as $icon => $path)
                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="icon"
                                value="{{ $icon }}"
                                class="peer sr-only"
                                @checked($icon === config('goal.default_icon'))
                            >
                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-outline-variant text-on-surface peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary peer-focus-visible:ring-2"
                                title="{{ __("goals.icons.{$icon}") }}"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">{!! $path !!}</svg>
                                <span class="sr-only">{{ __("goals.icons.{$icon}") }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-atoms.form-error for="icon" />
            </fieldset>

            <x-molecules.form-group name="note" type="text" :label="__('goals.fields.note')" maxlength="1000" />

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('goals.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('goals.index') }}" variant="secondary">
                    {{ __('goals.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>

    <x-organisms.calculator-modal />
@endsection

@push('scripts')
    @vite('resources/js/goals/form.js')
@endpush
