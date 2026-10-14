import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, put } from '../shared/http.js';
import { routes } from '../routes.js';
import '../shared/money-input.js';
import { wireDevTools } from '../goals/dev-tools.js';
import { subGoalItemFixture } from './dev-fixtures/item-form.js';

const form = document.getElementById('sub-goal-item-form');
const goalId = form?.dataset.goalId;
const itemId = form?.dataset.itemId;

wireDevTools(form, subGoalItemFixture);

async function loadItem() {
    const [subGoal, goal] = await Promise.all([get(routes.goals.subGoal(goalId, itemId)), get(routes.goals.show(goalId))]);

    if (subGoal.ok && goal.ok) {
        const data = subGoal.data.data;

        form.elements.namedItem('name').value = data.name ?? '';
        form.elements.namedItem('target_amount').value = data.target_amount ?? '';
        form.elements.namedItem('saved_amount').value = data.saved_amount ?? '';
        form.elements.namedItem('desired_date').value = data.desired_date ?? '';
        form.elements.namedItem('desired_date').max = goal.data.data.desired_date ?? '';
        form.elements.namedItem('is_done').checked = Boolean(data.is_done);
        form.elements.namedItem('note').value = data.note ?? '';

        if (goal.data.data.status === 'reached') {
            showGenericError(form, form.dataset.lockedMessage, 'name');
            form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
        }
    } else {
        showGenericError(form, 'This sub goal could not be found.', 'name');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    }

    hideSkeleton('sub-goal-item-form', 'sub-goal-item-form-skeleton');
}

if (form) {
    loadItem();

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
            saved_amount: form.elements.namedItem('saved_amount').value,
            desired_date: form.elements.namedItem('desired_date').value || null,
            is_done: form.elements.namedItem('is_done').checked,
            note: form.elements.namedItem('note').value || null,
        };

        try {
            const { ok, status, data } = await put(routes.goals.subGoal(goalId, itemId), payload);

            if (ok) {
                sessionStorage.setItem('sub-goal-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.goalSubGoalShow(goalId, itemId);
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
