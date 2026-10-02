<aside
    data-sidebar
    class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-hidden border-r border-outline-variant bg-surface transition-all duration-200 ease-in-out lg:static lg:translate-x-0"
>
    <div class="flex h-16 items-center justify-between gap-2 border-b border-outline-variant px-6">
        <a href="{{ route('dashboard') }}" class="truncate font-heading text-headline-sm font-bold text-on-surface" data-sidebar-label>
            {{ config('app.name') }}
        </a>
        <button
            type="button"
            data-sidebar-close
            class="-mr-2 inline-flex cursor-pointer items-center justify-center rounded-md p-2 text-on-surface-variant hover:bg-surface-container lg:hidden"
            aria-label="{{ __('dashboard.topbar.toggle_sidebar') }}"
        >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex flex-col gap-1 p-4 font-body text-body-md">
        @foreach ($navItems as $item)
            <a
                href="{{ route($item['route']) }}"
                title="{{ __($item['label']) }}"
                class="flex items-center gap-3 rounded-md px-3 py-2.5 transition-colors {{ $isActive($item['active'] ?? $item['route']) ? 'bg-primary-container text-on-primary-container font-medium' : 'text-on-surface-variant hover:bg-surface-container' }}"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    {!! $icons[$item['icon']] !!}
                </svg>
                <span class="truncate" data-sidebar-label>{{ __($item['label']) }}</span>
            </a>
        @endforeach
    </nav>
</aside>

<div
    data-sidebar-backdrop
    class="fixed inset-0 z-30 hidden bg-inverse-surface/40 lg:hidden"
></div>
