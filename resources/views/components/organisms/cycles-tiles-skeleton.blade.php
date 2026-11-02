@props(['class' => ''])

<div {{ $attributes->merge(['class' => $class]) }}>
    <x-atoms.skeleton class="h-20 w-full rounded-lg" />

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (range(1, 6) as $item)
            <x-atoms.skeleton class="h-20 rounded-lg" />
        @endforeach
    </div>
</div>
