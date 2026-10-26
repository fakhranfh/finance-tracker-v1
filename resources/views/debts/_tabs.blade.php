<nav class="mb-6 flex gap-1 border-b border-outline-variant font-body text-body-md" aria-label="{{ __('dashboard.nav.debts') }}">
    @foreach (['debt' => 'debts', 'receivable' => 'receivables'] as $tabType => $tabRoute)
        <a
            href="{{ route($tabRoute.'.index') }}"
            @class([
                '-mb-px border-b-2 px-4 py-2',
                'border-primary font-medium text-primary' => $type === $tabType,
                'border-transparent text-on-surface-variant hover:text-on-surface' => $type !== $tabType,
            ])
        >
            {{ __('debts.pages.'.$tabType.'.title') }}
        </a>
    @endforeach
</nav>
