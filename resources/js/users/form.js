import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { userFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('user-form');
const userId = form?.dataset.userId;

wireDevTools(form, userFormFixture);

async function loadUser() {
    const { ok, data } = await get(routes.users.show(userId));

    if (ok) {
        form.name.value = data.data.name;
        form.email.value = data.data.email;
    } else {
        showGenericError(form, 'This user could not be found.');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    }

    hideSkeleton('user-form', 'user-form-skeleton');
}

if (form) {
    if (userId) {
        loadUser();
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
            name: form.name.value,
            email: form.email.value,
            password: form.password.value,
            password_confirmation: form.password_confirmation.value,
        };

        try {
            const { ok, status, data } = await (userId ? put(routes.users.update(userId), payload) : post(routes.users.store, payload));

            if (ok) {
                sessionStorage.setItem('users-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.users;
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.');
            }
        } catch {
            showGenericError(form, 'Something went wrong.');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}
