import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { showSuccess } from '../shared/form-success.js';
import { setButtonLoading } from '../shared/loading-state.js';
import { post } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { forgotPasswordFixture } from './dev-fixtures/forgot-password.js';

const form = document.getElementById('forgot-password-form');

wireDevTools(form, forgotPasswordFixture);

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
        };

        try {
            const { ok, status, data } = await post(routes.auth.forgotPassword, payload);

            if (ok) {
                showSuccess(form, data.message);
                form.reset();
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
