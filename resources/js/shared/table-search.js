/**
 * Debounced search filter. The toolbar input ([data-table-search]) drives it;
 * the mobile modal input ([data-modal-search]) mirrors into it.
 * `onInput()` fires immediately, `onChange()` after the debounce.
 */
export function createTableSearch({ input, onInput = () => {}, onChange, delay = 350 }) {
    let timer = null;

    input?.addEventListener('input', () => {
        onInput();
        clearTimeout(timer);
        timer = setTimeout(onChange, delay);
    });

    document.querySelector('[data-modal-search]')?.addEventListener('input', (event) => {
        if (input) {
            input.value = event.target.value;
            input.dispatchEvent(new Event('input'));
        }
    });

    return {
        get value() {
            return input?.value;
        },

        applyToUrl(url) {
            if (input?.value) {
                url.searchParams.set('search', input.value);
            }
        },
    };
}
