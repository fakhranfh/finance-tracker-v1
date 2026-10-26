<nav class="mb-6 flex gap-1 border-b border-outline-variant font-body text-body-md" aria-label="{{ __('dashboard.nav.debts') }}">
    @foreach (['debts', 'receivables'] as $tab)
        <a
            href="{{ route($tab.'.index') }}"
            @class([
                '-mb-px border-b-2 px-4 py-2',
                'border-primary font-medium text-primary' => request()->routeIs($tab.'.*'),
                'border-transparent text-on-surface-variant hover:text-on-surface' => ! request()->routeIs($tab.'.*'),
            ])
        >
            {{ __($tab.'.title') }}
        </a>
    @endforeach
</nav>

