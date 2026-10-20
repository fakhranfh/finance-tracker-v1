/**
 * Formats `input[data-money]` as Indonesian-style whole numbers while typing ("1.250.000").
 * The visible text is formatted, but `input.value` stays the plain API integer
 * ("1250000"), so forms, fixtures and the calculator keep reading and writing raw values.
 */
const valueDescriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

/** Typed text (digits, "." grouping) to the grouped display string. */
function formatDisplay(text) {
    const digits = text.replace(/\D/g, '').replace(/^0+(?=\d)/, '');

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/** Raw API integer ("1250000") to the display string ("1.250.000"). */
function rawToDisplay(raw) {
    return formatDisplay(String(raw ?? '').split('.')[0]);
}

/** Display string back to the raw integer the API expects. */
function displayToRaw(display) {
    return display.replace(/\./g, '');
}

/** Puts the currency symbol inside the input's left edge. */
function addCurrencyPrefix(input) {
    const wrapper = document.createElement('div');
    const prefix = document.createElement('span');

    wrapper.className = 'relative';
    prefix.className = 'pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 font-body text-body-md text-on-surface-variant';
    prefix.textContent = 'Rp';
    prefix.setAttribute('aria-hidden', 'true');
    input.classList.add('!pl-11');

    input.replaceWith(wrapper);
    wrapper.append(prefix, input);
}

function wire(input) {
    addCurrencyPrefix(input);

    const read = () => valueDescriptor.get.call(input);
    const write = (display) => valueDescriptor.set.call(input, display);

    Object.defineProperty(input, 'value', {
        get: () => displayToRaw(read()),
        set: (raw) => write(rawToDisplay(raw)),
    });

    input.addEventListener('input', () => {
        const typed = read();
        const caret = input.selectionStart ?? typed.length;
        const significantBeforeCaret = typed.slice(0, caret).replace(/[^\d,]/g, '').length;
        const display = formatDisplay(typed);

        if (display === typed) {
            return;
        }

        write(display);

        if (document.activeElement === input) {
            let seen = 0;
            let position = 0;

            while (position < display.length && seen < significantBeforeCaret) {
                seen += /[\d,]/.test(display[position]) ? 1 : 0;
                position += 1;
            }

            input.setSelectionRange(position, position);
        }
    });

    write(rawToDisplay(read()));
}

/** Formats every `input[data-money]` inside root (the whole page by default), e.g. rows added to a form later. */
export function wireMoneyInputs(root = document) {
    root.querySelectorAll('input[data-money]').forEach(wire);
}

wireMoneyInputs();
