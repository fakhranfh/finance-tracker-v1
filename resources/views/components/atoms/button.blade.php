@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        <x-atoms.spinner class="hidden" data-button-spinner />
        <span data-button-label class="inline-flex items-center gap-2">{{ $slot }}</span>
    </button>
@endif
