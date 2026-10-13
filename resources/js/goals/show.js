import { hideSkeleton, setButtonLoading } from '../shared/loading-state.js';
import { clearFieldErrors, showFieldErrors, showGenericError } from '../shared/field-errors.js';
import { closeModal, openModal } from '../shared/modal.js';
import '../shared/money-input.js';
import { del, get, getAll, post, put } from '../shared/http.js';
import { createTable } from '../shared/table/table.js';
import { formatDate, formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';
import { fetchSubGoalSummary, summaryText } from '../sub-goals/summary.js';

const root = document.getElementById('goal-show');
const icons = JSON.parse(root?.dataset.icons ?? '{}');

let goal = null;
let savingsTable = null;
let editingSavingId = null;
let hasSubGoals = false;

const field = (name) => root.querySelector(`[data-field="${name}"]`);

function iconNode(name, color) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.5');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('class', 'size-8');
    svg.style.color = color;
    svg.innerHTML = icons[name] ?? '';

    return svg;
}

/** Projects when the goal is reached from the average saving pace since it was created. */
function estimateText(current) {
    const target = Number(current.target_amount);
    const saved = Number(current.saved_amount);

    if (saved >= target) {
        return root.dataset.estimateReached;
    }

    const elapsedDays = Math.max(1, (Date.now() - new Date(current.created_at).getTime()) / 86400000);
    const daysLeft = (target - saved) / (saved / elapsedDays);

    if (!Number.isFinite(daysLeft) || daysLeft > 36500) {
        return root.dataset.estimateUnknown;
    }

    const date = new Date(Date.now() + daysLeft * 86400000);
    const iso = [date.getFullYear(), date.getMonth() + 1, date.getDate()].map((part) => String(part).padStart(2, '0')).join('-');
    const relative = new Intl.RelativeTimeFormat(document.documentElement.lang || 'en', { numeric: 'always' });
    const months = daysLeft / 30;
    const distance =
        daysLeft < 60 ? relative.format(Math.ceil(daysLeft), 'day') : months < 24 ? relative.format(Math.round(months), 'month') : relative.format(Math.round(months / 12), 'year');

    return `${formatDate(iso)} (${distance})`;
}

function render(current) {
    goal = current;

    const target = Number(current.target_amount);
    const saved = Number(current.saved_amount);
    const progress = target > 0 ? Math.min(100, Math.round((saved / target) * 100)) : 0;

    field('icon').replaceChildren(iconNode(current.icon, current.color));
    field('name').textContent = current.name;
    field('progress').setAttribute('stroke-dashoffset', 100 - progress);
    field('progress_label').textContent = `${progress}%`;
    field('target_amount').textContent = formatMoney(current.target_amount);
    field('saved_amount').textContent = formatMoney(current.saved_amount);
    field('estimated_completion').textContent = estimateText(current);
    field('desired_date').textContent = formatDate(current.desired_date);
    field('note').textContent = current.note || '-';

    const locked = current.status === 'reached';
    document.getElementById('goal-edit-link').classList.toggle('!hidden', locked);
    const paused = current.status === 'paused';
    document.getElementById('set-reached-open').classList.toggle('!hidden', locked || paused);
    // A goal with sub goals is saved to through its sub goals, so it has no add saved amount button.
    document.getElementById('add-saved-open').classList.toggle('!hidden', locked || paused || hasSubGoals);
    document.getElementById('delete-goal-open').classList.toggle('!hidden', paused);
    document.getElementById('goal-locked-notice').classList.toggle('hidden', !locked);
}

/** Fills the sub goal selects of the add / edit saving forms; the saving history only shows its sub goal column when the goal has sub goals. */
async function loadSubGoalOptions() {
    const subGoals = (await getAll(routes.goals.subGoals(goal.id))) ?? [];

    hasSubGoals = subGoals.length > 0;
    document.getElementById('goal-savings-wrapper').toggleAttribute('data-no-sub-goals', !hasSubGoals);
    render(goal);

    document.querySelectorAll('[data-sub-goal-field]').forEach((wrapper) => {
        const select = wrapper.querySelector('select');

        select.querySelectorAll('option[value]:not([value=""])').forEach((option) => option.remove());
        subGoals.forEach((subGoal) => select.append(new Option(subGoal.name, subGoal.id)));
        wrapper.classList.toggle('hidden', !hasSubGoals);
    });
}

const subGoalValue = (form) => form.elements.namedItem('sub_goal_id').value || null;

async function loadSubGoalSummary() {
    const section = document.getElementById('goal-sub-goals');
    const summary = await fetchSubGoalSummary(goal.id);

    if (summary) {
        section.querySelector('[data-sub-goals-summary]').textContent = summary.total > 0 ? summaryText(section.dataset.summaryTemplate, summary) : section.dataset.empty;
    }
}

async function refreshGoal() {
    const { ok, data } = await get(routes.goals.show(goal.id));

    if (ok) {
        render(data.data);
    }
}

function showFlash(message) {
    const flash = document.querySelector('[data-flash-success]');

    flash.textContent = message;
    flash.classList.remove('hidden');
}

function openEditSaving(saving) {
    const form = document.getElementById('edit-saving-form');

    editingSavingId = saving.id;
    clearFieldErrors(form);
    form.elements.namedItem('saving_amount').value = saving.amount;
    form.elements.namedItem('sub_goal_id').value = saving.sub_goal_id ?? '';
    openModal('edit-saving-modal');
}

async function editSaving(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const amount = Number(form.elements.namedItem('saving_amount').value);

    clearFieldErrors(form);

    if (!(amount > 0)) {
        showGenericError(form, form.dataset.invalidMessage, 'saving_amount');
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    setButtonLoading(submitButton, true);

    try {
        const { ok, status, data } = await put(routes.goals.saving(goal.id, editingSavingId), { amount, sub_goal_id: subGoalValue(form) });

        if (ok) {
            render(data.goal);
            closeModal('edit-saving-modal');
            await savingsTable.reload();
            showFlash(root.dataset.savingUpdated);
        } else if (status === 422 && data.errors) {
            showFieldErrors(form, { saving_amount: data.errors.amount ?? Object.values(data.errors)[0], ...(data.errors.sub_goal_id && { sub_goal_id: data.errors.sub_goal_id }) });
        } else {
            showGenericError(form, data.message ?? 'Something went wrong.', 'saving_amount');
        }
    } catch {
        showGenericError(form, 'Something went wrong.', 'saving_amount');
    } finally {
        setButtonLoading(submitButton, false);
    }
}

/** The mobile filter/sort bar only shows while the saving history is on screen, so it doesn't cover the rest of the page. */
function revealMobileBarWhileTableInView() {
    const bar = document.getElementById('goal-savings-mobile-bar');

    new IntersectionObserver(([entry]) => bar.classList.toggle('!hidden', !entry.isIntersecting)).observe(
        document.getElementById('goal-savings-wrapper'),
    );
}

function createSavingsTable() {
    return createTable({
        ids: {
            table: 'goal-savings-table',
            wrapper: 'goal-savings-wrapper',
            generateButton: 'generate-savings',
            generateCount: 'generate-savings-count',
            confirmDelete: 'confirm-delete-saving',
            confirmBulkDelete: 'confirm-bulk-delete-savings',
        },
        filtersId: 'goal-savings-filters',
        flashKey: 'goal-savings-flash',
        selectionKey: 'goal-savings-selection',
        deleteModalId: 'delete-saving-modal',
        bulkDeleteModalId: 'bulk-delete-savings-modal',
        deletedMessage: root.dataset.savingDeleted,
        defaultSort: { key: 'created_at', direction: 'desc' },
        routes: {
            index: routes.goals.savings(goal.id),
            destroy: (savingId) => routes.goals.saving(goal.id, savingId),
            bulkDestroy: routes.goals.bulkDestroySavings(goal.id),
            generate: routes.goals.generateSavings(goal.id),
        },
        // A saving changes the goal's saved amount, so refresh the goal after a delete or generate.
        onDataChanged: refreshGoal,
        row: (item) => ({
            cells: [formatDateTime(item.created_at), item.type === 'done' ? root.dataset.savingDone : formatMoney(item.amount), item.sub_goal_name ?? '-'],
            onEdit: () => openEditSaving(item),
            readOnly: goal.status !== 'active',
            noEdit: item.type === 'done',
            locked: goal.status !== 'active',
        }),
    });
}

async function addSaved(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const amount = Number(form.elements.namedItem('amount').value);

    clearFieldErrors(form);

    if (!(amount > 0)) {
        showGenericError(form, form.dataset.invalidMessage, 'amount');
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    setButtonLoading(submitButton, true);

    try {
        const { ok, status, data } = await post(routes.goals.savings(goal.id), { amount, sub_goal_id: subGoalValue(form) });

        if (ok) {
            render(data.goal);
            await savingsTable.reload();
            closeModal('add-saved-modal');
            form.reset();

            const flash = document.querySelector('[data-flash-success]');
            flash.textContent = root.dataset.savedAdded;
            flash.classList.remove('hidden');
        } else if (status === 422 && data.errors) {
            showFieldErrors(form, { amount: data.errors.amount ?? Object.values(data.errors)[0], ...(data.errors.sub_goal_id && { sub_goal_id: data.errors.sub_goal_id }) });
        } else {
            showGenericError(form, data.message ?? 'Something went wrong.', 'amount');
        }
    } catch {
        showGenericError(form, 'Something went wrong.', 'amount');
    } finally {
        setButtonLoading(submitButton, false);
    }
}

async function setReached() {
    const button = document.getElementById('confirm-set-reached');
    const flash = document.querySelector('[data-flash-success]');
    const error = document.querySelector('[data-status-error]');

    error.classList.add('hidden');
    setButtonLoading(button, true);

    try {
        const { ok, data } = await put(routes.goals.update(goal.id), { ...goal, status: 'reached' });

        if (ok) {
            render(data.data);
            savingsTable.reload();
            closeModal('set-reached-modal');
            flash.textContent = root.dataset.reachedSet;
            flash.classList.remove('hidden');
        } else {
            closeModal('set-reached-modal');
            error.classList.remove('hidden');
        }
    } catch {
        closeModal('set-reached-modal');
        error.classList.remove('hidden');
    } finally {
        setButtonLoading(button, false);
    }
}

async function deleteGoal() {
    const button = document.getElementById('confirm-delete-goal');

    setButtonLoading(button, true);

    try {
        const { ok } = await del(routes.goals.destroy(goal.id));

        if (ok) {
            sessionStorage.setItem('goals-flash-success', root.dataset.deleted);
            window.location.href = routes.pages.goals;
            return;
        }
    } catch {
        // fall through to the error notice below
    }

    closeModal('delete-goal-modal');
    document.querySelector('[data-status-error]').classList.remove('hidden');
    setButtonLoading(button, false);
}

if (root) {
    const { ok, data } = await get(routes.goals.show(root.dataset.itemId));

    if (ok) {
        render(data.data);
        savingsTable = createSavingsTable();
        loadSubGoalSummary();
        loadSubGoalOptions();
        revealMobileBarWhileTableInView();
        hideSkeleton('goal-show', 'goal-show-skeleton');

        document.getElementById('delete-goal-open').addEventListener('click', () => openModal('delete-goal-modal'));
        document.getElementById('confirm-delete-goal').addEventListener('click', deleteGoal);
        document.getElementById('add-saved-open').addEventListener('click', () => openModal('add-saved-modal'));
        document.getElementById('set-reached-open').addEventListener('click', () => openModal('set-reached-modal'));
        document.getElementById('confirm-set-reached').addEventListener('click', setReached);
        document.getElementById('edit-saving-form').addEventListener('submit', editSaving);
        document.getElementById('add-saved-form').addEventListener('submit', addSaved);
    } else {
        document.getElementById('goal-show-skeleton').classList.add('hidden');
        document.getElementById('goal-show-missing').classList.remove('hidden');
    }
}
