@props([
    'options' => [10, 25, 50, 100],
    'label' => 'Items per page',
])

<label {{ $attributes->merge(['class' => 'flex items-center gap-2 font-body text-body-sm text-on-surface-variant']) }}>
    <span>{{ $label }}</span>
    <select
        data-per-page-select
        class="cursor-pointer rounded-md border border-outline-variant bg-surface px-2 py-1.5 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20"
    >
        @foreach ($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </select>
</label>
