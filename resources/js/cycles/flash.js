const KEY = 'cycles-flash-success';

export function showFlash(message) {
    const flash = document.querySelector('[data-flash-success]');

    document.querySelector('[data-flash-error]')?.classList.add('hidden');
    flash.textContent = message;
    flash.classList.remove('hidden');
}

export function showError(message) {
    const error = document.querySelector('[data-flash-error]');

    document.querySelector('[data-flash-success]')?.classList.add('hidden');
    error.textContent = message;
    error.classList.remove('hidden');
}

/** A flash set before a redirect to another cycles page (e.g. after a delete) shows once on the next page. */
export function flashNextPage(message) {
    sessionStorage.setItem(KEY, message);
}

export function showPendingFlash() {
    const message = sessionStorage.getItem(KEY);

    if (message) {
        sessionStorage.removeItem(KEY);
        showFlash(message);
    }
}
