import { hideSkeleton, setButtonLoading } from '../shared/loading-state.js';
import { get, put } from '../shared/http.js';
import { createTable } from '../shared/table/table.js';
import { formatDate, formatDateTime } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';

const root = document.getElementById('sub-goal-show');
const goalId = root?.dataset.goalId;
const itemId = root?.dataset.itemId;

let subGoal = null;
let locked = false;

const field = (name) => root.querySelector(`[data-field="${name}"]`);

function showFlash(message) {
    const flash = document.querySelector('[data-flash-success]');

    flash.textContent = message;
    flash.classList.remove('hidden');
}

function render(current, goal) {
    subGoal = current;

    const target = Number(current.target_amount);
    const saved = Number(current.saved_amount);
    const progress = target > 0 ? Math.min(100, Math.round((saved / target) * 100)) : 0;
    const link = document.createElement('a');

    link.href = routes.pages.goalShow(goalId);
    link.className = 'text-primary hover:underline';
    link.textContent = goal.name;

    field('name').textContent = current.name;
    field('progress').setAttribute('stroke-dashoffset', 100 - progress);
    field('progress_label').textContent = `${progress}%`;
    field('saved_amount_ring').textContent = formatMoney(current.saved_amount);
    field('target_amount_ring').textContent = formatMoney(current.target_amount);
    field('goal').replaceChildren(link);
    field('target_amount').textContent = formatMoney(current.target_amount);
    field('saved_amount').textContent = formatMoney(current.saved_amount);
    field('desired_date').textContent = current.desired_date ? formatDate(current.desired_date) : '-';
    field('status').textContent = current.is_done ? root.dataset.doneLabel : root.dataset.notDoneLabel;
    field('note').textContent = current.note || '-';

    locked = goal.status === 'reached';
    document.getElementById('sub-goal-edit-link').classList.toggle('!hidden', locked);
    document.getElementById('toggle-done').classList.toggle('!hidden', locked);
    document.getElementById('toggle-done').querySelector('[data-button-label]').textContent = current.is_done
        ? document.getElementById('toggle-done').dataset.notDoneLabel
        : document.getElementById('toggle-done').dataset.doneLabel;
    document.getElementById('sub-goal-locked-notice').classList.toggle('hidden', !locked);
}

/** The mobile sort bar only shows while the saving history is on screen, so it doesn't cover the rest of the page. */
function revealMobileBarWhileTableInView() {
    const bar = document.getElementById('sub-goal-savings-mobile-bar');

    new IntersectionObserver(([entry]) => bar.classList.toggle('!hidden', !entry.isIntersecting)).observe(
        document.getElementById('sub-goal-savings-wrapper'),
    );
}

function createSavingsTable() {
    return createTable({
        ids: {
            table: 'sub-goal-savings-table',
            wrapper: 'sub-goal-savings-wrapper',
        },
        flashKey: `sub-goal-savings-flash-${itemId}`,
        selectionKey: `sub-goal-savings-selection-${itemId}`,
        defaultSort: { key: 'created_at', direction: 'desc' },
        routes: {
            index: routes.goals.savings(goalId),
        },
        params: () => ({ sub_goal_id: itemId }),
        // The saving history is read-only here; savings are added and edited on the goal page.
        row: (item) => ({
            cells: [formatDateTime(item.created_at), formatMoney(item.amount)],
            readOnly: true,
            locked: true,
        }),
    });
}

async function toggleDone() {
    const button = document.getElementById('toggle-done');
    const error = document.querySelector('[data-status-error]');
    const goal = { name: field('goal').textContent, status: locked ? 'reached' : 'active' };

    error.classList.add('hidden');
    setButtonLoading(button, true);

    try {
        const { ok, data } = await put(routes.goals.subGoal(goalId, itemId), { is_done: !subGoal.is_done });

        if (ok) {
            render(data.data, goal);
            showFlash(data.data.is_done ? root.dataset.markedDone : root.dataset.markedNotDone);
        } else {
            error.classList.remove('hidden');
        }
    } catch {
        error.classList.remove('hidden');
    } finally {
        setButtonLoading(button, false);
    }
}

if (root) {
    const storedFlash = sessionStorage.getItem('sub-goal-flash-success');
    const [subGoalResponse, goalResponse] = await Promise.all([get(routes.goals.subGoal(goalId, itemId)), get(routes.goals.show(goalId))]);

    if (subGoalResponse.ok && goalResponse.ok) {
        render(subGoalResponse.data.data, goalResponse.data.data);
        createSavingsTable();
        revealMobileBarWhileTableInView();
        hideSkeleton('sub-goal-show', 'sub-goal-show-skeleton');

        if (storedFlash) {
            sessionStorage.removeItem('sub-goal-flash-success');
            showFlash(storedFlash);
        }

        document.getElementById('toggle-done').addEventListener('click', toggleDone);
    } else {
        document.getElementById('sub-goal-show-skeleton').classList.add('hidden');
        document.getElementById('sub-goal-show-missing').classList.remove('hidden');
    }
}
