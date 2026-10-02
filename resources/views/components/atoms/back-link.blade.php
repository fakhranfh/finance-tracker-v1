@props(['href'])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'mb-6 inline-flex items-center gap-2 font-body text-body-md text-on-surface-variant hover:text-on-surface']) }}
>
    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M12.5 15.5 7 10l5.5-5.5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    {{ $slot }}
</a>
