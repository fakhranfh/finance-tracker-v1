@props(['class' => ''])

<div {{ $attributes->merge(['class' => $class]) }}>
    <div class="mb-6 flex items-center gap-3">
        <x-atoms.skeleton class="size-8" />
        <x-atoms.skeleton class="h-7 w-48" />
    </div>

    <x-atoms.skeleton class="mx-auto mb-8 size-56 !rounded-full" />

    <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
        @foreach (range(1, 4) as $item)
            <div class="space-y-2">
                <x-atoms.skeleton class="h-4 w-28" />
                <x-atoms.skeleton class="h-5 w-40" />
            </div>
        @endforeach

        <div class="space-y-2 sm:col-span-2">
            <x-atoms.skeleton class="h-4 w-28" />
            <x-atoms.skeleton class="h-5 w-full" />
        </div>
    </div>

    <div class="mt-6 space-y-3 border-t border-outline-variant pt-6">
        <x-atoms.skeleton class="h-6 w-36" />
        <x-atoms.skeleton class="h-5 w-full" />
        <x-atoms.skeleton class="h-5 w-full" />
    </div>

    <div class="mt-6 flex items-center justify-between gap-3 border-t border-outline-variant pt-6">
        <x-atoms.skeleton class="h-11 w-24" />
        <div class="flex gap-3">
            <x-atoms.skeleton class="h-11 w-36" />
            <x-atoms.skeleton class="h-11 w-40" />
        </div>
    </div>
</div>
