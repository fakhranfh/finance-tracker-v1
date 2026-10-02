import { storeApiToken } from '../shared/api-token.js';
import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { post } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { loginFixture } from './dev-fixtures/login.js';

const form = document.getElementById('login-form');

wireDevTools(form, loginFixture);

form?.querySelector('[data-dev-auto-login]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    setButtonLoading(button, true);

    try {
        const { ok, data } = await post(routes.auth.devAutoLogin, {});

        if (ok) {
            storeApiToken(data.token);
            window.location.href = routes.pages.dashboard;
            return;
        }

        showGenericError(form, data.message ?? 'Auto login failed.');
    } catch {
        showGenericError(form, 'Auto login failed.');
    } finally {
        setButtonLoading(button, false);
    }
});

if (form) {
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
            email: form.email.value,
            password: form.password.value,
            remember: form.remember.checked,
        };

        try {
            const { ok, status, data } = await post(routes.auth.login, payload);

            if (ok) {
                storeApiToken(data.token);
                window.location.href = routes.pages.dashboard;
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? form.dataset.genericError ?? 'Something went wrong.');
            }
        } catch {
            showGenericError(form, form.dataset.genericError ?? 'Something went wrong.');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}
