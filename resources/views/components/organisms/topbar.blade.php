<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-outline-variant bg-surface px-4 sm:px-6">
    <button
        type="button"
        data-sidebar-toggle
        class="inline-flex cursor-pointer items-center justify-center rounded-md p-2 text-on-surface-variant hover:bg-surface-container lg:hidden"
        aria-label="{{ __('dashboard.topbar.toggle_sidebar') }}"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            {!! $icons['menu'] !!}
        </svg>
    </button>

    <button
        type="button"
        data-sidebar-collapse-toggle
        class="hidden cursor-pointer items-center justify-center rounded-md p-2 text-on-surface-variant hover:bg-surface-container lg:inline-flex"
        aria-label="{{ __('dashboard.topbar.toggle_sidebar') }}"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            {!! $icons['collapse'] !!}
        </svg>
    </button>

    <div class="flex-1"></div>

    <div class="flex items-center gap-4">
        <x-atoms.language-switcher />

        <a href="{{ route('profile.edit') }}" data-user-name class="hidden font-body text-body-md text-on-surface-variant hover:underline sm:inline">
            {{ auth()->user()->name }}
        </a>

        <x-atoms.button variant="secondary" data-logout-trigger>
            {{ __('dashboard.topbar.logout') }}
        </x-atoms.button>
    </div>
</header>
