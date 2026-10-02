@props([
    'name',
    'label',
    'type' => 'text',
])

<div class="space-y-1.5">
    <x-atoms.label :for="$name">{{ $label }}</x-atoms.label>
    <x-atoms.input :type="$type" :name="$name" :id="$name" {{ $attributes }} />
    <x-atoms.form-error :for="$name" />
</div>
