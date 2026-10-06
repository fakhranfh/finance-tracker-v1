<div id="account-cards" data-account-cards>
    <x-organisms.account-cards-skeleton id="account-cards-skeleton" />

    <div class="mb-3 hidden justify-end" data-account-cards-toolbar>
        <x-atoms.button type="button" variant="secondary" data-account-cards-reorder data-done-label="{{ __('accounts.order.done') }}">
            {{ __('accounts.order.start') }}
        </x-atoms.button>
    </div>

    <div id="account-cards-list" class="hidden grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5"></div>

    <div class="mt-4 hidden justify-center" data-account-cards-more>
        <x-atoms.button href="{{ route('accounts.index') }}" variant="secondary">
            {{ __('accounts.table.show_more') }}
        </x-atoms.button>
    </div>

    <template data-account-card-template>
        <div class="group relative flex h-16 items-center gap-3 rounded-lg px-4 text-white" data-account-card>
            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-account-card-icon></svg>
            <div class="min-w-0 flex-1">
                <p class="truncate font-body text-body-sm font-semibold" data-account-card-name></p>
                <p class="font-body text-body-sm" data-account-card-balance></p>
            </div>
            <div class="hidden shrink-0 items-center gap-1" data-account-card-move>
                <button type="button" class="cursor-pointer rounded-md p-1.5 hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40" data-account-card-move-left>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    <span class="sr-only">{{ __('accounts.order.move_left') }}</span>
                </button>
                <button type="button" class="cursor-pointer rounded-md p-1.5 hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40" data-account-card-move-right>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    <span class="sr-only">{{ __('accounts.order.move_right') }}</span>
                </button>
            </div>
            <button
                type="button"
                class="cursor-pointer rounded-md p-1.5 opacity-100 transition-opacity hover:bg-white/20 focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
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
