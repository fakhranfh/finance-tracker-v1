<template id="cycle-tile-template">
    <div class="relative flex min-h-20 items-center gap-1 rounded-lg border border-outline-variant bg-surface text-on-surface transition-colors hover:border-primary" data-card>
        <input type="checkbox" class="ml-3 size-5 shrink-0 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20" data-select>
        <a href="#" class="flex min-w-0 flex-1 items-center gap-4 self-stretch rounded-lg px-3 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" data-tile>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-8 shrink-0 text-primary" aria-hidden="true" data-field="icon"></svg>
            <span class="min-w-0">
                <span class="block break-words font-body text-body-md font-medium" data-field="name"></span>
                <span class="block break-words font-body text-body-sm text-on-surface-variant" data-field="meta"></span>
            </span>
        </a>
        <button type="button" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full hover:bg-primary-fixed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" data-edit>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
            </svg>
            <span class="sr-only">{{ __('cycles.detail.edit') }}</span>
        </button>
        <button type="button" class="mr-1 flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full hover:bg-primary-fixed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" data-delete>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
            <span class="sr-only">{{ __('cycles.detail.delete') }}</span>
        </button>
    </div>
</template>

<template id="cycle-add-tile-template">
    <button type="button" class="flex min-h-20 cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-outline bg-transparent p-4 text-center text-on-surface-variant transition-colors hover:border-primary hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" data-add-tile>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span class="font-body text-body-md font-medium" data-field="label"></span>
    </button>
</template>

<template id="cycle-entry-template">
    <li>
        <div class="rounded-lg border border-outline-variant bg-surface transition-colors hover:border-primary">
            <div class="flex items-start gap-3 p-4">
                <input type="checkbox" class="mt-1 size-5 shrink-0 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20" data-select>
                <a href="#" class="block min-w-0 flex-1 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" data-entry>
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 break-words font-heading text-headline-sm font-semibold text-on-surface" data-field="name"></p>
                        <span class="shrink-0 rounded-full px-2.5 py-1 font-body text-label-md font-medium" data-field="status"></span>
                    </div>
                    <p class="mt-1 font-body text-body-md text-on-surface-variant" data-field="date"></p>
                    <p class="font-body text-body-sm text-on-surface-variant" data-field="meta"></p>
                </a>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1 border-t border-outline-variant px-4 py-1 font-body text-body-sm">
                <button type="button" class="hidden min-h-11 cursor-pointer items-center gap-1.5 font-medium text-primary hover:underline" data-finish>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    {{ __('cycles.detail.finish') }}
                </button>
                <button type="button" class="min-h-11 cursor-pointer text-primary hover:underline" data-edit>{{ __('cycles.detail.edit') }}</button>
                <button type="button" class="min-h-11 cursor-pointer text-error hover:underline" data-delete>{{ __('cycles.detail.delete') }}</button>
            </div>
        </div>
    </li>
</template>
