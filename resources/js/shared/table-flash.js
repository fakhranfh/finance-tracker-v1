/**
 * Success flash message ([data-flash-success]). `showStored()` shows (and
 * clears) a message another page left in sessionStorage under `storageKey`,
 * e.g. after redirecting back from a create/edit form.
 */
export function createTableFlash({ element = document.querySelector('[data-flash-success]'), storageKey }) {
    return {
        show(message) {
            if (!element || !message) {
                return;
            }

            element.textContent = message;
            element.classList.remove('hidden');
        },

        hide() {
            element?.classList.add('hidden');
        },

        showStored() {
            const message = sessionStorage.getItem(storageKey);

            if (message) {
                this.show(message);
                sessionStorage.removeItem(storageKey);
            }
        },
    };
}
