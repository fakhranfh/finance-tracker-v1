/**
 * Formats `input[data-money]` as Indonesian-style numbers while typing ("1.250.000,5").
 * The visible text is formatted, but `input.value` stays the plain API number
 * ("1250000.5"), so forms, fixtures and the calculator keep reading and writing raw values.
 */
const MAX_DECIMALS = 2;
const valueDescriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

/** Typed text (digits, "." grouping, "," decimal) to the grouped display string. */
function formatDisplay(text) {
    const [integerPart, ...rest] = text.replace(/[^\d,]/g, '').split(',');
    const hasDecimal = rest.length > 0;
    const integer = integerPart.replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const decimals = rest.join('').slice(0, MAX_DECIMALS);

    return hasDecimal ? `${integer || '0'},${decimals}` : integer;
}

/** Raw API number ("1250000.50") to the display string ("1.250.000,5"). */
function rawToDisplay(raw) {
    const [integer, decimals = ''] = String(raw ?? '').replace(/[^\d.]/g, '').split('.');
    const trimmed = decimals.replace(/0+$/, '');

    return formatDisplay(trimmed ? `${integer},${trimmed}` : integer);
}

/** Display string back to the raw number the API expects. */
function displayToRaw(display) {
    return display.replace(/\./g, '').replace(',', '.').replace(/\.$/, '');
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

document.querySelectorAll('input[data-money]').forEach(wire);
