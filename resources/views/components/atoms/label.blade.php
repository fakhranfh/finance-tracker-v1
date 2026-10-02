@props(['for' => null])

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'block font-body text-label-md font-medium text-on-surface-variant']) }}>
    {{ $slot }}
</label>
