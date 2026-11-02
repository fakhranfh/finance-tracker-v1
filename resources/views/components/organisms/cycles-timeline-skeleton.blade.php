@props(['class' => ''])

<div {{ $attributes->merge(['class' => $class]) }}>
    <x-atoms.skeleton class="h-20 w-full rounded-lg" />

    <div class="mt-6 space-y-4">
        @foreach (range(1, 3) as $item)
            <div class="w-full space-y-2 rounded-lg border border-outline-variant bg-surface p-4">
                <x-atoms.skeleton class="h-5 w-32" />
                <x-atoms.skeleton class="h-4 w-48" />
                <x-atoms.skeleton class="h-6 w-24 !rounded-full" />
            </div>
        @endforeach
    </div>
</div>
