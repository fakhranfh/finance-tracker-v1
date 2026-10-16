import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { wireCopyButtons } from '../shared/copy-to-clipboard.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { webhookFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('webhook-form');
const itemId = form?.dataset.itemId;

wireDevTools(form, webhookFormFixture);
wireCopyButtons(form);

async function loadItem() {
    const { ok, data } = await get(routes.webhooks.show(itemId));

    if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('token').value = data.data.token ?? '';
        form.elements.namedItem('allowed_ips').value = (data.data.allowed_ips ?? []).join('\n');
        form.elements.namedItem('url').value = data.data.url ?? '';
        form.elements.namedItem('is_active').checked = Boolean(data.data.is_active);
    }

    hideSkeleton('webhook-form', 'webhook-form-skeleton');
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
            allowed_ips: form.elements.namedItem('allowed_ips').value,
            is_active: form.elements.namedItem('is_active').checked,
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.webhooks.update(itemId), payload) : post(routes.webhooks.store, payload));

            if (ok) {
                sessionStorage.setItem('webhooks-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.webhooks;
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
