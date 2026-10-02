import { scrollToField, showErrorSnackbar } from './error-snackbar.js';

export function clearFieldErrors(form) {
    form.querySelectorAll('[data-error-for]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

/**
 * Run the browser's built-in constraint validation (required, type=email,
 * minlength, pattern, etc.) against every named field, before a submission
 * ever reaches the API. Returns an `{ field: [message] }` map shaped like a
 * Laravel validation-error response, so it can be passed straight to
 * `showFieldErrors`.
 */
export function validateForm(form) {
    const errors = {};

    Array.from(form.elements).forEach((el) => {
        if (! el.name || typeof el.checkValidity !== 'function') {
            return;
        }

        if (! el.checkValidity()) {
            errors[el.name] = [el.validationMessage];
        }
    });

    return errors;
}

/**
 * Render each field's error next to its input and scroll to the first one.
 * When there are more than 3 errors, also show a clickable snackbar
 * ([data-error-snackbar] in the layout) listing every error; clicking an
 * item scrolls to its input.
 */
export function showFieldErrors(form, errors) {
    clearFieldErrors(form);

    const entries = Object.entries(errors ?? {});

    entries.forEach(([field, messages]) => {
        const el = form.querySelector(`[data-error-for="${field}"]`);
        if (el) {
            el.textContent = messages[0];
            el.classList.remove('hidden');
        }
    });

    if (entries.length === 0) {
        return;
    }

    scrollToField(form, entries[0][0]);

    if (entries.length > 3) {
        showErrorSnackbar(form, entries);
    }
}

/**
 * Show a single non-field error (e.g. invalid credentials, a network
 * failure) next to the given field's input, same as any other field error.
 * The snackbar is reserved for when there are more than 3 errors at once.
 */
export function showGenericError(form, message, field = 'email') {
    showFieldErrors(form, { [field]: [message] });
}
