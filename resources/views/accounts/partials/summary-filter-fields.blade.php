<div class="w-full sm:w-56">
    <label for="range" class="mb-1 block font-body text-body-sm font-medium text-on-surface">{{ __('accounts.summary.range') }}</label>
    <x-atoms.select name="range" id="range">
        @foreach ($presets as $value => $label)
            <option value="{{ $value }}" @selected($range['preset'] === $value)>{{ $label }}</option>
        @endforeach
    </x-atoms.select>
</div>

<div class="w-full sm:w-44">
    <label for="from" class="mb-1 block font-body text-body-sm font-medium text-on-surface">{{ __('accounts.summary.from') }}</label>
    <x-atoms.input type="date" name="from" id="from" value="{{ $range['preset'] === 'custom' ? $range['from'] : '' }}" />
</div>

<div class="w-full sm:w-44">
    <label for="to" class="mb-1 block font-body text-body-sm font-medium text-on-surface">{{ __('accounts.summary.to') }}</label>
    <x-atoms.input type="date" name="to" id="to" value="{{ $range['preset'] === 'custom' ? $range['to'] : '' }}" />
</div>

<x-atoms.button type="submit" variant="secondary" class="max-sm:w-full">{{ __('accounts.summary.apply') }}</x-atoms.button>
