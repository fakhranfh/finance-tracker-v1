<header class="border-b border-outline-variant bg-surface">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <a href="{{ route('landing') }}" class="font-heading text-headline-sm font-bold text-on-surface">
            {{ config('app.name') }}
        </a>

        <nav class="hidden items-center gap-6 font-body text-body-md text-on-surface-variant md:flex">
            <a href="#features" class="hover:text-on-surface">{{ __('landing.nav.features') }}</a>
            @auth
                <a href="{{ route('dashboard') }}" class="hover:text-on-surface">{{ __('landing.nav.dashboard') }}</a>
            @endauth
            @guest
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="hover:text-on-surface">{{ __('landing.nav.login') }}</a>
                @endif
            @endguest
        </nav>

        <div class="flex items-center gap-4">
            <x-atoms.language-switcher />
            @auth
                <span class="hidden font-body text-body-md text-on-surface-variant sm:inline">
                    {{ auth()->user()->name }}
                </span>
                <x-atoms.button :href="route('dashboard')" class="md:hidden">
                    {{ __('landing.nav.dashboard') }}
                </x-atoms.button>
                <x-atoms.button variant="secondary" data-logout-trigger>
                    {{ __('landing.nav.logout') }}
                </x-atoms.button>
            @else
                <x-atoms.button :href="Route::has('register') ? route('register') : '#'">
                    {{ __('landing.nav.get_started') }}
                </x-atoms.button>
            @endauth
        </div>
    </div>
</header>
