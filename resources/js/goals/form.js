import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, getAll, post, put } from '../shared/http.js';
import { routes } from '../routes.js';
import '../shared/money-input.js';
import '../shared/calculator/calculator.js';
import { wireDevTools } from './dev-tools.js';
import { goalFormFixture } from './dev-fixtures/form.js';
import { subGoalFixtures } from './dev-fixtures/sub-goals.js';
import { createSubGoalsField } from './sub-goals-field.js';

const form = document.getElementById('goal-form');
const itemId = form?.dataset.itemId;
let loadedStatus = 'active';

const colorInput = form?.querySelector('[data-color-hex]');
const colorLabel = form?.querySelector('[data-color-hex-label]');

function syncColorLabel() {
    if (colorInput && colorLabel) {
        colorLabel.textContent = colorInput.value.toUpperCase();
    }
}

colorInput?.addEventListener('input', syncColorLabel);
wireDevTools(form, goalFormFixture);
const subGoalsField = form ? createSubGoalsField({ form }) : null;

// A new goal also gets sample sub goals that split the autofilled target and saved amount.
document.querySelector('[data-dev-autofill]')?.addEventListener('click', () => {
    if (!itemId) {
        subGoalsField.fill(
            subGoalFixtures({
                target: form.elements.namedItem('target_amount').value,
                saved: form.elements.namedItem('saved_amount').value,
                desiredDate: form.elements.namedItem('desired_date').value,
            }),
        );
    }
});
document.querySelector('[data-dev-autofill]')?.addEventListener('click', syncColorLabel);

async function loadItem() {
    const { ok, data } = await get(routes.goals.show(itemId));

    if (ok && data.data.status === 'reached') {
        showGenericError(form, form.dataset.lockedMessage, 'name');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    } else if (ok) {
        form.elements.namedItem('name').value = data.data.name ?? '';
        form.elements.namedItem('target_amount').value = data.data.target_amount ?? '';
        form.elements.namedItem('saved_amount').value = data.data.saved_amount ?? '';
        form.elements.namedItem('desired_date').value = data.data.desired_date ?? '';
        form.elements.namedItem('color').value = data.data.color ?? '';
        form.elements.namedItem('icon').value = data.data.icon ?? '';
        loadedStatus = data.data.status ?? 'active';
        form.elements.namedItem('note').value = data.data.note ?? '';
        syncColorLabel();
    } else {
        showGenericError(form, 'This goal could not be found.', 'name');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    }

    const subGoals = ok ? await getAll(routes.goals.subGoals(itemId)) : null;

    if (subGoals) {
        subGoalsField.load(subGoals);
    } else {
        subGoalsField.markUnloaded();
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

        const clientErrors = { ...validateForm(form), ...subGoalsField.check() };
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
            ...(itemId ? { status: loadedStatus } : {}),
            note: form.elements.namedItem('note').value || null,
            sub_goals: subGoalsField.payload(),
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
