<template id="cycle-tile-template">
    <div class="relative flex flex-col rounded-lg border border-outline-variant bg-surface transition-colors hover:border-primary">
        <input type="checkbox" class="absolute left-3 top-3 z-10 size-5 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20" data-select>
        <a href="#" class="flex min-h-36 flex-1 flex-col items-center justify-center gap-2 rounded-t-lg p-4 text-center hover:bg-primary-fixed/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" data-tile>
            <span class="flex size-14 items-center justify-center rounded-full bg-primary-fixed text-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7" aria-hidden="true" data-field="icon"></svg>
            </span>
            <span class="font-body text-body-md font-medium text-on-surface" data-field="name"></span>
            <span class="font-body text-body-sm text-on-surface-variant" data-field="meta"></span>
        </a>
        <div class="flex items-center justify-center gap-4 border-t border-outline-variant px-2 font-body text-body-sm">
            <button type="button" class="min-h-11 cursor-pointer text-primary hover:underline" data-edit>{{ __('cycles.detail.edit') }}</button>
            <button type="button" class="min-h-11 cursor-pointer text-error hover:underline" data-delete>{{ __('cycles.detail.delete') }}</button>
        </div>
    </div>
</template>

<template id="cycle-add-tile-template">
    <button type="button" class="flex min-h-36 cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-outline bg-transparent p-4 text-center text-on-surface-variant transition-colors hover:border-primary hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40" data-add-tile>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7" aria-hidden="true">
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
