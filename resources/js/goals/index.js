import { createTable } from '../shared/table/table.js';
import { formatDate } from '../shared/datetime.js';
import { formatMoney } from '../shared/money.js';
import { routes } from '../routes.js';

const table = document.getElementById('goals-table');
const icons = JSON.parse(table?.dataset.icons ?? '{}');
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
            statusLabels[item.status] ?? item.status ?? '',
            colorNode(item.color ?? ''),
            iconNode(item.icon, item.color),
            item.note ?? '',
        ],
        editUrl: routes.pages.goalEdit(item.id),
        locked: false,
    }),
});
