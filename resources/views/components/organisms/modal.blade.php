@props(['id', 'title' => null])

<div
    id="{{ $id }}"
    data-modal
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="{{ $id }}-title" @endif
    class="fixed inset-0 z-50 hidden items-center justify-center bg-inverse-surface/40 p-4"
>
    <div class="w-full max-w-md rounded-lg bg-surface p-6 shadow-lg">
        @if ($title)
            <h2 id="{{ $id }}-title" class="mb-4 font-heading text-headline-sm font-bold text-on-surface">
                {{ $title }}
            </h2>
        @endif

        {{ $slot }}
    </div>
</div>
