import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { post } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { resetPasswordFixture } from './dev-fixtures/reset-password.js';

const form = document.getElementById('reset-password-form');

wireDevTools(form, resetPasswordFixture);

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
            token: form.dataset.token,
            email: form.email.value,
            password: form.password.value,
            password_confirmation: form.password_confirmation.value,
        };

        try {
            const { ok, status, data } = await post(routes.auth.resetPassword, payload);

            if (ok) {
                window.location.href = routes.pages.login;
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
