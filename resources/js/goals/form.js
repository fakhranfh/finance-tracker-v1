import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import '../shared/calculator/calculator.js';
import { wireDevTools } from './dev-tools.js';
import { goalFormFixture } from './dev-fixtures/form.js';

const form = document.getElementById('goal-form');
const itemId = form?.dataset.itemId;

const colorInput = form?.querySelector('[data-color-hex]');
const colorLabel = form?.querySelector('[data-color-hex-label]');

function syncColorLabel() {
    if (colorInput && colorLabel) {
        colorLabel.textContent = colorInput.value.toUpperCase();
    }
}

colorInput?.addEventListener('input', syncColorLabel);
wireDevTools(form, goalFormFixture);
document.querySelector('[data-dev-autofill]')?.addEventListener('click', syncColorLabel);

async function loadItem() {
    const { ok, data } = await get(routes.goals.show(itemId));

    if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('target_amount').value = data.data.target_amount ?? '';
        form.elements.namedItem('saved_amount').value = data.data.saved_amount ?? '';
        form.elements.namedItem('desired_date').value = data.data.desired_date ?? '';
        form.elements.namedItem('color').value = data.data.color ?? '';
        form.elements.namedItem('icon').value = data.data.icon ?? '';
        form.elements.namedItem('note').value = data.data.note ?? '';
        syncColorLabel();
    } else {
        showGenericError(form, 'This goal could not be found.', 'name');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    }

    hideSkeleton('goal-form', 'goal-form-skeleton');
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
            target_amount: form.elements.namedItem('target_amount').value,
            saved_amount: form.elements.namedItem('saved_amount').value,
            desired_date: form.elements.namedItem('desired_date').value,
            color: form.elements.namedItem('color').value,
            icon: form.elements.namedItem('icon').value,
            note: form.elements.namedItem('note').value || null,
        };

        try {
            const { ok, status, data } = await (itemId ? put(routes.goals.update(itemId), payload) : post(routes.goals.store, payload));

            if (ok) {
                sessionStorage.setItem('goals-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.goals;
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
