import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from '../users/dev-tools.js';
import { userFormFixture } from '../users/dev-fixtures/form.js';

const form = document.getElementById('profile-form');
const flash = document.querySelector('[data-flash-success]');

wireDevTools(form, () => {
    const { name, email } = userFormFixture();

    return { name, email };
});

async function loadProfile() {
    const { ok, data } = await get(routes.profile.show);

    if (ok) {
        form.name.value = data.data.name;
        form.email.value = data.data.email;
    }

    hideSkeleton('profile-form', 'profile-form-skeleton');
}

if (form) {
    loadProfile();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearFieldErrors(form);
        hideSnackbar();
        flash?.classList.add('hidden');

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
            current_password: form.current_password.value,
            password: form.password.value,
            password_confirmation: form.password_confirmation.value,
        };

        try {
            const { ok, status, data } = await put(routes.profile.update, payload);

            if (ok) {
                if (!data.data.email_verified) {
                    window.location.href = form.dataset.verifyUrl;
                    return;
                }

                form.name.value = data.data.name;
                form.email.value = data.data.email;
                form.current_password.value = '';
                form.password.value = '';
                form.password_confirmation.value = '';
                document.querySelectorAll('[data-user-name]').forEach((element) => {
                    element.textContent = data.data.name;
                });
                flash.textContent = form.dataset.successMessage;
                flash.classList.remove('hidden');
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
