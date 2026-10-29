import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { userFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('user-form');
const itemId = form?.dataset.itemId;

wireDevTools(form, userFormFixture);

async function loadItem() {
    const { ok, data } = await get(routes.users.show(itemId));

    if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('email').value = data.data.email ?? '';
        form.elements.namedItem('role').value = data.data.role ?? 'user';
    }

    hideSkeleton('user-form', 'user-form-skeleton');
}

if (form) {
    if (itemId) {
        loadItem();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearFieldErrors(form);
        hideSnackbar();

        const clientErrors = validateForm(form);
        if (Object.keys(clientErrors).length > 0) {
            showFieldErrors(form, clientErrors);
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        setButtonLoading(submitButton, true);

        const payload = {
            name: form.elements.namedItem('name').value,
            email: form.elements.namedItem('email').value,
            role: form.elements.namedItem('role').value,
            password: form.elements.namedItem('password').value,
            password_confirmation: form.elements.namedItem('password_confirmation').value,
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.users.update(itemId), payload) : post(routes.users.store, payload));

            if (ok) {
                sessionStorage.setItem('users-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.users;
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.', 'name');
            }
        } catch {
            showGenericError(form, 'Something went wrong.', 'name');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}
