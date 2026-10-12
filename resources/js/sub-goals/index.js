import { createTable } from '../shared/table/table.js';
import { formatDate } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { get, put } from '../shared/http.js';
import { routes } from '../routes.js';
import { fetchSubGoalSummary, summaryText } from './summary.js';

const page = document.getElementById('sub-goals-page');
const goalId = page?.dataset.goalId;
const statusError = document.querySelector('[data-status-error]');
const summaryNode = document.querySelector('[data-sub-goals-summary]');

let locked = false;

async function refreshSummary() {
    const summary = await fetchSubGoalSummary(goalId);

    if (summary) {
        summaryNode.textContent = summaryText(page.dataset.summaryTemplate, summary);
        summaryNode.classList.remove('hidden');
    }
}

/** Inline checkbox: saves the sub goal as done / not done and reverts the tick if that fails. */
function doneNode(item) {
    const label = document.createElement('label');
    label.className = 'inline-flex cursor-pointer items-center gap-2';

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'h-4 w-4 cursor-pointer rounded border-outline-variant text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-default';
    checkbox.checked = item.is_done;
    checkbox.disabled = locked;
    checkbox.setAttribute('aria-label', item.name);

    const text = document.createElement('span');
    text.textContent = item.is_done ? page.dataset.doneLabel : page.dataset.notDoneLabel;

    checkbox.addEventListener('change', async () => {
        checkbox.disabled = true;
        statusError.classList.add('hidden');

        try {
            const { ok } = await put(routes.goals.subGoal(goalId, item.id), { is_done: checkbox.checked });

            if (ok) {
                item.is_done = checkbox.checked;
                text.textContent = item.is_done ? page.dataset.doneLabel : page.dataset.notDoneLabel;
                refreshSummary();
            } else {
                checkbox.checked = item.is_done;
                statusError.classList.remove('hidden');
            }
        } catch {
            checkbox.checked = item.is_done;
            statusError.classList.remove('hidden');
        } finally {
            checkbox.disabled = locked;
        }
    });

    label.append(checkbox, text);

    return label;
}

if (page) {
    const { ok, data } = await get(routes.goals.show(goalId));

    if (ok) {
        locked = data.data.status === 'reached';
        document.querySelector('[data-goal-name]').textContent = data.data.name;
        document.getElementById('sub-goals-locked-notice').classList.toggle('hidden', !locked);
    }

    refreshSummary();

    createTable({
        ids: {
            table: 'sub-goals-table',
            wrapper: 'sub-goals-table-wrapper',
            toolbar: 'sub-goals-table-toolbar',
        },
        flashKey: 'sub-goals-flash-success',
        selectionKey: `sub-goals-table-selection-${goalId}`,
        defaultSort: { key: 'created_at', direction: 'asc' },
        routes: {
            index: routes.goals.subGoals(goalId),
        },
        // Sub goals are edited together with their goal, so the row's edit link opens the goal form and nothing can be deleted here.
        row: (item) => ({
            cells: [
                item.name ?? '',
                formatMoney(item.target_amount),
                formatMoney(item.saved_amount),
                item.desired_date ? formatDate(item.desired_date) : '-',
                doneNode(item),
                item.note ?? '',
            ],
            viewUrl: routes.pages.goalSubGoalShow(goalId, item.id),
            editUrl: routes.pages.goalSubGoalEdit(goalId, item.id),
            readOnly: locked,
            locked: true,
        }),
    });
}
