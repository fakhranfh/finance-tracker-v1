<div id="account-cards" data-account-cards>
    <x-organisms.account-cards-skeleton id="account-cards-skeleton" />

    <div id="account-cards-list" class="hidden grid grid-cols-3 gap-3 sm:grid-cols-2 lg:grid-cols-5"></div>

    <x-molecules.pagination data-account-cards-pagination />

    <template data-account-card-template>
        <div class="group relative flex h-16 min-w-0 items-center gap-2 rounded-lg px-2 text-white sm:gap-3 sm:px-4" data-account-card>
            <svg class="hidden h-6 w-6 shrink-0 sm:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-account-card-icon></svg>
            <div class="min-w-0 flex-1 overflow-hidden">
                <p class="truncate font-body text-[11px] font-semibold sm:text-body-sm" data-account-card-name></p>
                <p class="truncate font-body text-[11px] sm:text-body-sm" data-account-card-balance></p>
            </div>
            <button
                type="button"
                class="hidden shrink-0 cursor-pointer rounded-md p-1 opacity-100 sm:block sm:p-1.5 transition-opacity hover:bg-white/20 focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
                data-account-card-edit
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                <span class="sr-only">{{ __('accounts.actions.edit') }}</span>
            </button>
        </div>
    </template>

    <template data-account-card-add>
        <a
            href="{{ route('accounts.create') }}"
            class="flex h-16 items-center justify-center rounded-lg border-2 border-dashed border-outline-variant font-body text-body-sm text-on-surface-variant hover:bg-surface-container-low"
        >
            <span aria-hidden="true">+</span>
            <span class="sr-only">{{ __('accounts.create') }}</span>
        </a>
    </template>

    @foreach ($icons as $icon => $path)
        <template data-icon-template="{{ $icon }}">{!! $path !!}</template>
    @endforeach

    <x-organisms.modal id="account-appearance-modal" :title="__('accounts.appearance.title')">
        <form id="account-appearance-form" novalidate class="space-y-5">
            <fieldset class="space-y-2">
                <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('accounts.appearance.color') }}</legend>
                <input
                    type="color"
                    name="color"
                    class="h-10 w-14 cursor-pointer rounded-md border border-outline-variant bg-surface p-1"
                >
            </fieldset>

            <fieldset class="space-y-2">
                <legend class="font-body text-body-sm font-medium text-on-surface">{{ __('accounts.appearance.icon') }}</legend>
                <div class="flex flex-wrap gap-3">
                    @foreach ($icons as $icon => $path)
                        <label class="cursor-pointer">
                            <input type="radio" name="icon" value="{{ $icon }}" class="peer sr-only">
                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-outline-variant text-on-surface peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary peer-focus-visible:ring-2"
                                title="{{ $iconLabels[$icon] }}"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">{!! $path !!}</svg>
                                <span class="sr-only">{{ $iconLabels[$icon] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex justify-end gap-3">
                <x-atoms.button type="button" variant="secondary" data-modal-close>
                    {{ __('accounts.actions.cancel') }}
                </x-atoms.button>
                <x-atoms.button type="submit">
                    {{ __('accounts.actions.save') }}
                </x-atoms.button>
            </div>
        </form>
    </x-organisms.modal>
</div>
