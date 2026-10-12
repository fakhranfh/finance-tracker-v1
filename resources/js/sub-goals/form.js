import { hideSnackbar } from '../shared/error-snackbar.js';
import { clearFieldErrors, showFieldErrors, showGenericError, validateForm } from '../shared/field-errors.js';
import { setButtonLoading, hideSkeleton } from '../shared/loading-state.js';
import { get, getAll, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { createSubGoalsField } from '../goals/sub-goals-field.js';
import { subGoalFixtures } from '../goals/dev-fixtures/sub-goals.js';

const form = document.getElementById('sub-goal-form');
const goalId = form?.dataset.goalId;

/**
 * Edits all sub goals of one goal on their own page. The goal's target amount, saved amount and desired date
 * sit in hidden inputs: the repeater checks the rows against them and the server checks against the stored goal.
 */
async function loadGoal(subGoalsField) {
    const { ok, data } = await get(routes.goals.show(goalId));
    const subGoals = ok ? await getAll(routes.goals.subGoals(goalId)) : null;

    if (!ok || !subGoals) {
        showGenericError(form, 'This goal could not be found.', 'sub_goals');
        form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
    } else {
        document.querySelector('[data-goal-name]').textContent = data.data.name;
        form.elements.namedItem('target_amount').value = data.data.target_amount;
        form.elements.namedItem('saved_amount').value = data.data.saved_amount;
        form.elements.namedItem('desired_date').value = data.data.desired_date;
        subGoalsField.load(subGoals);

        if (data.data.status === 'reached') {
            showGenericError(form, form.dataset.lockedMessage, 'sub_goals');
            form.querySelector('[type="submit"]')?.setAttribute('disabled', '');
        }
    }

    hideSkeleton('sub-goal-form', 'sub-goal-form-skeleton');
}

if (form) {
    const subGoalsField = createSubGoalsField({ form });

    loadGoal(subGoalsField);

    document.querySelector('[data-dev-autofill]')?.addEventListener('click', () => {
        subGoalsField.fill(
            subGoalFixtures({
                target: form.elements.namedItem('target_amount').value,
                saved: form.elements.namedItem('saved_amount').value,
                desiredDate: form.elements.namedItem('desired_date').value,
            }),
        );
    });

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

        try {
            const { ok, status, data } = await put(routes.goals.subGoals(goalId), { sub_goals: subGoalsField.payload() });

            if (ok) {
                sessionStorage.setItem('sub-goals-flash-success', form.dataset.successMessage);
                window.location.href = routes.pages.goalSubGoals(goalId);
                return;
            }

            if (status === 422 && data.errors) {
                showFieldErrors(form, data.errors);
            } else {
                showGenericError(form, data.message ?? 'Something went wrong.', 'sub_goals');
            }
        } catch {
            showGenericError(form, 'Something went wrong.', 'sub_goals');
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}
