<div class="flex items-center gap-1 font-body text-label-md">
    <a
        href="{{ route('locale.switch', 'en') }}"
        class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-sm px-2 {{ $current === 'en' ? 'bg-surface-container text-on-surface' : 'text-on-surface-variant hover:text-on-surface' }}"
    >EN</a>
    <a
        href="{{ route('locale.switch', 'id') }}"
        class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-sm px-2 {{ $current === 'id' ? 'bg-surface-container text-on-surface' : 'text-on-surface-variant hover:text-on-surface' }}"
    >ID</a>
</div>
