<x-organisms.modal id="calculator-modal" :title="__('calculator.title')">
    <div class="mb-4 rounded-md border border-outline-variant bg-surface-container px-4 py-3 text-right" aria-live="polite">
        <div class="min-h-6 break-all font-body text-body-md text-on-surface-variant" data-calc-expression>0</div>
        <div class="min-h-8 break-all font-heading text-headline-sm font-bold text-on-surface" data-calc-result></div>
    </div>

    <div class="grid grid-cols-4 gap-2">
        @foreach (['clear', 'back', '/', '*', '7', '8', '9', '-', '4', '5', '6', '+', '1', '2', '3', 'equals'] as $key)
            <x-atoms.button variant="secondary" class="w-full justify-center" data-calc-key="{{ $key }}">
                {{ ['clear' => 'C', 'back' => '⌫', '/' => '÷', '*' => '×', '-' => '−', 'equals' => '='][$key] ?? $key }}
            </x-atoms.button>
        @endforeach
        <x-atoms.button variant="secondary" class="col-span-2 w-full justify-center" data-calc-key="0">0</x-atoms.button>
        <x-atoms.button variant="secondary" class="w-full justify-center" data-calc-key=".">.</x-atoms.button>
        <x-atoms.button variant="secondary" class="w-full justify-center" data-calc-key="00">00</x-atoms.button>
    </div>

    <div class="mt-4 flex items-center gap-3">
        <x-atoms.button type="button" data-calc-apply>{{ __('calculator.apply') }}</x-atoms.button>
        <x-atoms.button type="button" variant="secondary" data-modal-close>{{ __('calculator.cancel') }}</x-atoms.button>
    </div>
</x-organisms.modal>
