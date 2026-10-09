import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { wireDevTools } from './dev-tools.js';
import { categoryFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('category-form');
const itemId = form?.dataset.itemId;

const colorInput = form?.querySelector('[data-color-hex]');
const colorLabel = form?.querySelector('[data-color-hex-label]');

function syncColorLabel() {
    if (colorInput && colorLabel) {
        colorLabel.textContent = colorInput.value.toUpperCase();
    }
}

colorInput?.addEventListener('input', syncColorLabel);
wireDevTools(form, categoryFormFixture);
document.querySelector('[data-dev-autofill]')?.addEventListener('click', syncColorLabel);

async function loadItem() {
    const { ok, data } = await get(routes.categories.show(itemId));

    if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('type').value = data.data.type ?? '';
        form.elements.namedItem('color').value = data.data.color ?? '';
        form.elements.namedItem('icon').value = data.data.icon ?? '';
        syncColorLabel();
    } else {
        showGenericError(form, 'This category could not be found.', 'name');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    }

    hideSkeleton('category-form', 'category-form-skeleton');
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
            type: form.elements.namedItem('type').value,
            color: form.elements.namedItem('color').value,
            icon: form.elements.namedItem('icon').value,
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.categories.update(itemId), payload) : post(routes.categories.store, payload));

            if (ok) {
                sessionStorage.setItem('categories-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.categories;
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
