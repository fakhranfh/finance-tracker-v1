import { hideSkeleton, setButtonLoading } from '../shared/loading-state.js';
import { clearFieldErrors, showFieldErrors, showGenericError } from '../shared/field-errors.js';
import { closeModal, openModal } from '../shared/modal.js';
import '../shared/money-input.js';
import { del, get, put } from '../shared/http.js';
import { formatDate } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';

const root = document.getElementById('goal-show');
const icons = JSON.parse(root?.dataset.icons ?? '{}');
const statusLabels = JSON.parse(root?.dataset.statusLabels ?? '{}');

let goal = null;

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

function colorNode(color) {
    const wrapper = document.createElement('span');
    wrapper.className = 'inline-flex items-center gap-2';

    const swatch = document.createElement('span');
    swatch.className = 'size-4 rounded-full border border-outline-variant';
    swatch.style.backgroundColor = color;

    const hex = document.createElement('span');
    hex.textContent = color.toUpperCase();

    wrapper.append(swatch, hex);

    return wrapper;
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
    field('status').textContent = statusLabels[current.status] ?? current.status;
    field('color').replaceChildren(colorNode(current.color));
    field('note').textContent = current.note || '-';

    const locked = current.status === 'reached';
    document.getElementById('goal-edit-link').classList.toggle('!hidden', locked);
    ['set-reached-open', 'add-saved-open'].forEach((id) => document.getElementById(id).classList.toggle('!hidden', locked));
    document.getElementById('goal-locked-notice').classList.toggle('hidden', !locked);
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
        const saved = (Math.round((Number(goal.saved_amount) + amount) * 100) / 100).toFixed(2);
        const { ok, status, data } = await put(routes.goals.update(goal.id), { ...goal, saved_amount: saved });

        if (ok) {
            render(data.data);
            closeModal('add-saved-modal');
            form.reset();

            const flash = document.querySelector('[data-flash-success]');
            flash.textContent = root.dataset.savedAdded;
            flash.classList.remove('hidden');
        } else if (status === 422 && data.errors) {
            showFieldErrors(form, { amount: data.errors.saved_amount ?? Object.values(data.errors)[0] });
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
        hideSkeleton('goal-show', 'goal-show-skeleton');

        document.getElementById('delete-goal-open').addEventListener('click', () => openModal('delete-goal-modal'));
        document.getElementById('confirm-delete-goal').addEventListener('click', deleteGoal);
        document.getElementById('add-saved-open').addEventListener('click', () => openModal('add-saved-modal'));
        document.getElementById('set-reached-open').addEventListener('click', () => openModal('set-reached-modal'));
        document.getElementById('confirm-set-reached').addEventListener('click', setReached);
        document.getElementById('add-saved-form').addEventListener('submit', addSaved);
    } else {
        document.getElementById('goal-show-skeleton').classList.add('hidden');
        document.getElementById('goal-show-missing').classList.remove('hidden');
    }
}
