import { createTable } from '../shared/table/table.js';
import { formatDate } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { put } from '../shared/http.js';
import { routes } from '../routes.js';

const table = document.getElementById('goals-table');
const icons = JSON.parse(table?.dataset.icons ?? '{}');
const statusError = document.querySelector('[data-status-error]');
const statusLabels = JSON.parse(table?.dataset.statusLabels ?? '{}');

function iconNode(name, color) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.5');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('class', 'size-6');
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

/** Inline status dropdown: saves the goal with the new status and reverts the choice if that fails. */
function statusNode(item) {
    const select = document.createElement('select');
    select.className =
        'rounded-md border border-outline-variant bg-surface px-2 py-1 font-body text-body-sm text-on-surface focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-50';
    select.setAttribute('aria-label', item.name);

    Object.entries(statusLabels).forEach(([value, label]) => select.append(new Option(label, value)));
    select.value = item.status;

    select.addEventListener('change', async () => {
        select.disabled = true;
        statusError?.classList.add('hidden');

        try {
            const { ok } = await put(routes.goals.update(item.id), { ...item, status: select.value });

            if (ok) {
                item.status = select.value;
            } else {
                select.value = item.status;
                statusError?.classList.remove('hidden');
            }
        } catch {
            select.value = item.status;
            statusError?.classList.remove('hidden');
        } finally {
            select.disabled = false;
        }
    });

    return select;
}

createTable({
    ids: {
        table: 'goals-table',
        wrapper: 'goals-table-wrapper',
        toolbar: 'goals-table-toolbar',
        generateButton: 'generate-goals',
        generateCount: 'generate-count',
        confirmDelete: 'confirm-delete-goal',
        confirmBulkDelete: 'confirm-bulk-delete-goals',
    },
    flashKey: 'goals-flash-success',
    selectionKey: 'goals-table-selection',
    deleteModalId: 'delete-goal-modal',
    bulkDeleteModalId: 'bulk-delete-goals-modal',
    defaultSort: { key: 'desired_date', direction: 'asc' },
    routes: {
        index: routes.goals.index,
        destroy: routes.goals.destroy,
        bulkDestroy: routes.goals.bulkDestroy,
        generate: routes.goals.generate,
    },
    row: (item) => ({
        cells: [
            item.name ?? '',
            formatMoney(item.target_amount),
            formatMoney(item.saved_amount),
            formatDate(item.desired_date),
            statusNode(item),
            colorNode(item.color ?? ''),
            iconNode(item.icon, item.color),
            item.note ?? '',
        ],
        viewUrl: routes.pages.goalShow(item.id),
        editUrl: routes.pages.goalEdit(item.id),
        locked: false,
    }),
});
