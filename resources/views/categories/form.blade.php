@extends('layouts.dashboard')

@section('title', ($id ? __('categories.edit') : __('categories.create')))

@section('content')
    <div class="mb-6 flex items-end justify-between gap-3">
        <div>
            <a href="{{ route('categories.index') }}" class="font-body text-body-sm text-on-surface-variant hover:underline">
                &larr; {{ __('categories.back') }}
            </a>
            <h1 class="mt-2 font-heading text-headline-md font-bold text-on-surface">
                {{ $id ? __('categories.edit') : __('categories.create') }}
            </h1>
        </div>

        @if (app()->isLocal())
            <x-atoms.button type="button" variant="secondary" data-dev-autofill>
                {{ __('categories.actions.autofill') }}
            </x-atoms.button>
        @endif
    </div>

    <div class="w-full rounded-lg border border-outline-variant bg-surface p-6">
        @if ($id)
            <x-organisms.category-form-skeleton id="category-form-skeleton" />
        @endif

        <form
            id="category-form"
            @class(['space-y-5', 'hidden' => $id])
            novalidate
            @if ($id) data-item-id="{{ $id }}" @endif
            data-success-message="{{ $id ? __('categories.flash.updated') : __('categories.flash.created') }}"
        >
            @csrf

            <x-molecules.form-group name="name" type="text" :label="__('categories.fields.name')" required autofocus />

            <div class="space-y-1.5">
                <x-atoms.label>{{ __('categories.fields.type') }}</x-atoms.label>
                <div class="grid grid-cols-2 gap-2" role="radiogroup">
                    @foreach ($typeLabels as $value => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-md border border-outline-variant px-3 py-2.5 font-body text-body-md text-on-surface-variant transition-colors has-checked:border-primary has-checked:bg-primary-fixed has-checked:font-medium has-checked:text-primary has-focus-visible:ring-2 has-focus-visible:ring-primary/20">
                            <input type="radio" name="type" value="{{ $value }}" class="sr-only" @checked($value === 'expense') required>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <x-atoms.form-error for="type" />
            </div>

            <fieldset class="space-y-2">
                <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('categories.fields.color') }}</legend>
                <div class="flex items-center gap-3">
                    <input
                        type="color"
                        name="color"
                        value="{{ config('category.default_color') }}"
                        class="h-10 w-14 cursor-pointer rounded-md border border-outline-variant bg-surface p-1"
                        data-color-hex
                    >
                    <span class="font-body text-body-sm text-on-surface-variant" data-color-hex-label>{{ config('category.default_color') }}</span>
                </div>
                <x-atoms.form-error for="color" />
            </fieldset>

            <fieldset class="space-y-2">
                <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('categories.fields.icon') }}</legend>
                <div class="flex flex-wrap gap-3">
                    @foreach (config('category.icons') as $icon => $path)
                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="icon"
                                value="{{ $icon }}"
                                class="peer sr-only"
                                @checked($icon === config('category.default_icon'))
                            >
                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-outline-variant text-on-surface peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary peer-focus-visible:ring-2"
                                title="{{ __("categories.icons.{$icon}") }}"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">{!! $path !!}</svg>
                                <span class="sr-only">{{ __("categories.icons.{$icon}") }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-atoms.form-error for="icon" />
            </fieldset>

            <div class="flex items-center gap-3">
                <x-atoms.button type="submit">
                    {{ __('categories.actions.save') }}
                </x-atoms.button>
                <x-atoms.button href="{{ route('categories.index') }}" variant="secondary">
                    {{ __('categories.actions.cancel') }}
                </x-atoms.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/categories/form.js')
@endpush
