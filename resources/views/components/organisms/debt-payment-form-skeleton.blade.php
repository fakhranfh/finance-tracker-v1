@props(['class' => '', 'fields' => 3])

<div {{ $attributes->merge(['class' => "space-y-5 {$class}"]) }}>
    @for ($field = 0; $field < $fields; $field++)
        <div class="space-y-1.5">
            <x-atoms.skeleton class="h-4 w-24" />
            <x-atoms.skeleton class="h-10 w-full" />
        </div>
    @endfor
    <x-atoms.skeleton class="h-11 w-32" />
</div>
